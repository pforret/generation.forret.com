<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Resolves a person to their English Wikipedia article and pulls:
 * - the first paragraph of the article lead (plain text),
 * - the canonical Wikipedia URL,
 * - the IMDb URL (via the Wikidata item's P345 "IMDb ID" claim).
 *
 * A candidate article is only accepted when its Wikidata birth date (P569) matches the
 * expected birth year, so a name that hits a namesake or disambiguation page is rejected.
 */
class WikipediaPersonLookup
{
    private const WIKIPEDIA_API = 'https://en.wikipedia.org/w/api.php';

    private const WIKIDATA_API = 'https://www.wikidata.org/w/api.php';

    private const USER_AGENT = 'generation.forret.com/1.0 (https://generation.forret.com/)';

    /**
     * @return array{title:string, url_wikipedia:string, url_imdb:?string, description:string}|null
     */
    public function lookup(string $name, int $birthYear, ?string $wikipediaUrl = null): ?array
    {
        $candidates = $wikipediaUrl
            ? [$this->titleFromUrl($wikipediaUrl)]
            : array_unique(array_merge([$name], $this->search("{$name} born {$birthYear}")));

        foreach ($candidates as $title) {
            $page = $this->page($title);
            if (! $page || ! $page['qid']) {
                continue;
            }
            $claims = $this->wikidataClaims($page['qid']);
            // A given URL is trusted; otherwise the birth year must confirm the match.
            if (! $wikipediaUrl && ! in_array($birthYear, $this->birthYears($claims), true)) {
                continue;
            }
            $imdb = $claims['P345'][0]['mainsnak']['datavalue']['value'] ?? null;

            return [
                'title' => $page['title'],
                'url_wikipedia' => $page['url'],
                'url_imdb' => ($imdb && str_starts_with($imdb, 'nm')) ? "https://www.imdb.com/name/{$imdb}/" : null,
                'description' => $page['intro'],
            ];
        }

        return null;
    }

    /** @return string[] article titles */
    private function search(string $query): array
    {
        $json = $this->get(self::WIKIPEDIA_API, [
            'action' => 'query', 'list' => 'search', 'srsearch' => $query,
            'srlimit' => 5, 'format' => 'json', 'formatversion' => 2,
        ]);

        return array_column($json['query']['search'] ?? [], 'title');
    }

    /** @return array{title:string, url:string, qid:?string, intro:string}|null */
    private function page(string $title): ?array
    {
        $json = $this->get(self::WIKIPEDIA_API, [
            'action' => 'query', 'titles' => $title, 'redirects' => 1,
            'prop' => 'extracts|pageprops|info', 'exintro' => 1, 'explaintext' => 1,
            'ppprop' => 'wikibase_item|disambiguation', 'inprop' => 'url',
            'format' => 'json', 'formatversion' => 2,
        ]);
        $page = $json['query']['pages'][0] ?? null;
        if (! $page || isset($page['missing']) || isset($page['pageprops']['disambiguation'])) {
            return null;
        }

        return [
            'title' => $page['title'],
            'url' => $page['canonicalurl'] ?? $page['fullurl'],
            'qid' => $page['pageprops']['wikibase_item'] ?? null,
            'intro' => $this->firstParagraph((string) ($page['extract'] ?? '')),
        ];
    }

    private function wikidataClaims(string $qid): array
    {
        $json = $this->get(self::WIKIDATA_API, [
            'action' => 'wbgetentities', 'ids' => $qid, 'props' => 'claims', 'format' => 'json',
        ]);

        return $json['entities'][$qid]['claims'] ?? [];
    }

    /**
     * All P569 birth-date claims, since disputed dates carry several
     * (Khomeini: 1902, and 1900 per his birth certificate).
     *
     * @return int[]
     */
    private function birthYears(array $claims): array
    {
        $years = [];
        foreach ($claims['P569'] ?? [] as $claim) {
            $time = $claim['mainsnak']['datavalue']['value']['time'] ?? ''; // "+1940-04-25T00:00:00Z"
            if (preg_match('/^[+-]?(\d+)-/', $time, $m)) {
                $years[] = (int) $m[1];
            }
        }

        return $years;
    }

    private function firstParagraph(string $extract): string
    {
        foreach (preg_split('/\n+/', $extract) as $para) {
            $para = trim($para);
            if ($para !== '') {
                $para = preg_replace_callback('/\(([^()]*)\)/u', fn ($m) => $this->cleanParenthetical($m[1]), $para);

                return trim(preg_replace(['/\s{2,}/u', '/\s+([,.;])/u'], [' ', '$1'], $para));
            }
        }

        return '';
    }

    /**
     * Plain-text extracts keep the leftovers of pronunciation markup in the lead's
     * parenthetical, e.g. "( sar-KOH-zee; French: [nikɔla ...] ; born 28 January 1955)".
     * Drop IPA segments ("[...]") and respellings ("GOSS-ling", "də NEER-roh", "YAY"), even
     * when embedded: "born Kanye Omari West  KAHN-yay oh-MAH-ree, June 8, 1977".
     */
    private function cleanParenthetical(string $inner): string
    {
        $keep = [];
        foreach (explode(';', $inner) as $seg) {
            $seg = trim($seg);
            if ($seg === '' || str_contains($seg, '[') || preg_match('/^\p{Lu}{2,}$/u', $seg)) {
                continue;
            }
            $pieces = [];
            foreach (explode(',', $seg) as $piece) {
                // Cut the piece at the first respelled word (hyphenated, with an ALL-CAPS syllable).
                $words = [];
                foreach (preg_split('/\s+/u', trim($piece)) as $word) {
                    if (preg_match('/(^|-)\p{Lu}{2,}-|-\p{Lu}{2,}($|-)/u', $word)) {
                        break;
                    }
                    $words[] = $word;
                }
                $piece = implode(' ', $words);
                // What remains of a respelling like "də" has no capitals or digits: drop it.
                if ($piece !== '' && preg_match('/[\p{Lu}\d]/u', $piece)) {
                    $pieces[] = $piece;
                }
            }
            if ($pieces) {
                $keep[] = implode(', ', $pieces);
            }
        }

        return $keep ? '('.implode('; ', $keep).')' : '';
    }

    private function titleFromUrl(string $url): string
    {
        return str_replace('_', ' ', rawurldecode(Str::afterLast(parse_url($url, PHP_URL_PATH) ?? '', '/wiki/')));
    }

    private function get(string $url, array $query): array
    {
        return Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->retry(3, 1000)
            ->timeout(20)
            ->get($url, $query)
            ->throw()
            ->json() ?? [];
    }
}
