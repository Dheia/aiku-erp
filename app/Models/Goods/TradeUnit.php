<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 03 May 2023 11:42:04 Malaysia Time, Pantai Lembeng, Bali, Id
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Models\Goods;

use App\Models\Inventory\Stock;
use App\Models\Market\Product;
use App\Models\Traits\HasImages;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * App\Models\Goods\TradeUnit
 *
 * @property int $id
 * @property string $slug
 * @property string $code
 * @property string|null $name
 * @property string|null $description
 * @property string|null $barcode
 * @property float|null $gross_weight in kilograms include packing
 * @property float|null $net_weight in kilograms
 * @property array|null $dimensions
 * @property float|null $volume in cubic meters
 * @property string|null $type unit type
 * @property int|null $image_id
 * @property array $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $source_type
 * @property int|null $source_id
 * @property-read MediaCollection<int, \App\Models\Media\Media> $media
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Stock> $stocks
 * @method static \Database\Factories\Goods\TradeUnitFactory factory($count = null, $state = [])
 * @method static Builder|TradeUnit newModelQuery()
 * @method static Builder|TradeUnit newQuery()
 * @method static Builder|TradeUnit onlyTrashed()
 * @method static Builder|TradeUnit query()
 * @method static Builder|TradeUnit withTrashed()
 * @method static Builder|TradeUnit withoutTrashed()
 * @mixin Eloquent
 */
class TradeUnit extends Model implements HasMedia
{
    use SoftDeletes;
    use HasSlug;

    use HasImages;
    use HasFactory;

    protected $casts = [
        'data'       => 'array',
        'dimensions' => 'array',
    ];

    protected $attributes = [
        'data'       => '{}',
        'dimensions' => '{}',
    ];

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('code')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function stocks(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
