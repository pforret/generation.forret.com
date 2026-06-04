<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Generation;
use App\Models\Person;
use Illuminate\Support\Collection;

/**
 * Shared "anchoring" logic that ties people and events to a generation.
 *
 * The relationship is derived at query time (there is no foreign key):
 *  - People  = born-in / cohort     (Person.born_at within [first_year, last_year]).
 *  - Events  = peaked/happened-during, bucketed into the life stage the cohort was in.
 *
 * Extracted from GenerationController::show / PersonController::show so the
 * controllers and the static-site generator share one implementation.
 */
class GenerationAnchor
{
    /** Life-stage windows = cohort age (in years) when an event happened. */
    public const LIFE_STAGES = [
        'child' => [6, 12],
        'puberty' => [13, 20],
        'adult' => [21, 60],
        'retired' => [61, 80],
    ];

    /** The cohort's middle birth year, used as the life-stage reference point. */
    public function middleYear(Generation $generation): int
    {
        return (int) round(($generation->first_year + $generation->last_year) / 2);
    }

    /** Build a Jan-1 Y-m-d date string for $year shifted by $offset years. */
    public function fmt(int $year, int $offset = 0): string
    {
        return sprintf('%04d-%02d-%02d', $year + $offset, 1, 1);
    }

    /** People whose birth year falls inside the cohort's range. */
    public function peopleBornIn(Generation $generation): Collection
    {
        return Person::whereBetween('born_at', [
            $this->fmt($generation->first_year),
            $this->fmt($generation->last_year + 1),
        ])->orderBy('born_at')->orderBy('name')->get();
    }

    /**
     * Events grouped by the life stage the cohort was in when they happened.
     *
     * @return array<string, Collection> keyed by child|puberty|adult|retired
     */
    public function eventsByLifeStage(Generation $generation): array
    {
        $middle = $this->middleYear($generation);
        $events = [];
        foreach (self::LIFE_STAGES as $stage => [$from, $to]) {
            $events[$stage] = Event::whereBetween('happened_at', [
                $this->fmt($middle, $from),
                $this->fmt($middle, $to),
            ])->orderBy('happened_at')->get();
        }

        return $events;
    }

    /** The generation whose range contains $year, or null. */
    public function generationForYear(int $year): ?Generation
    {
        return Generation::where('first_year', '<=', $year)
            ->where('last_year', '>=', $year)
            ->first();
    }

    /**
     * Life stage the cohort (middle birth year) was in when an event happened.
     * Returns child|puberty|adult|retired, or null if not-yet-born / beyond range.
     */
    public function lifeStageAtYear(Generation $generation, int $eventYear): ?string
    {
        $age = $eventYear - $this->middleYear($generation);
        foreach (self::LIFE_STAGES as $stage => [$from, $to]) {
            if ($age >= $from && $age <= $to) {
                return $stage;
            }
        }

        return null;
    }
}
