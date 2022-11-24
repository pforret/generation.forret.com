<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Generation
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $alternatives
 * @property int $first_year
 * @property int $last_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\GenerationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Generation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Generation query()
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereAlternatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereFirstYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Generation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Generation extends Model
{
    use HasFactory;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
    ];
}
