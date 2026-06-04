<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Person
 *
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property string|null $description
 * @property string|null $born_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Person newModelQuery()
 * @method static Builder|Person newQuery()
 * @method static Builder|Person query()
 * @method static Builder|Person whereBornAt($value)
 * @method static Builder|Person whereCategory($value)
 * @method static Builder|Person whereCreatedAt($value)
 * @method static Builder|Person whereDescription($value)
 * @method static Builder|Person whereId($value)
 * @method static Builder|Person whereTitle($value)
 * @method static Builder|Person whereUpdatedAt($value)
 */
class Person extends Model
{

    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
    ];

    protected $casts = [
        "born" => "date",
    ];
}
