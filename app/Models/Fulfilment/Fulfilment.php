<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 24 Jan 2024 10:56:13 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Models\Fulfilment;

use App\Models\Inventory\Warehouse;
use App\Models\Market\Shop;
use App\Models\SysAdmin\Group;
use App\Models\SysAdmin\Organisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * App\Models\Fulfilment\Fulfilment
 *
 * @property int $id
 * @property int $group_id
 * @property int $organisation_id
 * @property int $shop_id
 * @property string $slug
 * @property int $number_warehouses
 * @property array $data
 * @property array $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $source_id
 * @property-read Group $group
 * @property-read Organisation $organisation
 * @property-read Shop $shop
 * @property-read \App\Models\Fulfilment\FulfilmentStats|null $stats
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Warehouse> $warehouses
 * @method static \Illuminate\Database\Eloquent\Builder|Fulfilment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fulfilment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fulfilment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Fulfilment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Fulfilment withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Fulfilment withoutTrashed()
 * @mixin \Eloquent
 */
class Fulfilment extends Model
{
    use SoftDeletes;
    use HasSlug;

    protected $casts = [
        'data'     => 'array',
        'settings' => 'array',
    ];

    protected $attributes = [
        'data'     => '{}',
        'settings' => '{}',
    ];

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function () {
                return $this->shop->slug;
            })
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(6);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function stats(): HasOne
    {
        return $this->hasOne(FulfilmentStats::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class);
    }

}
