<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 23 Feb 2023 22:27:25 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Models\Dispatch;

use App\Models\Search\UniversalSearch;
use App\Models\Traits\HasUniversalSearch;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * App\Models\Dispatch\Shipment
 *
 * @property-read \App\Models\Dispatch\Shipper|null $shipper
 * @property-read UniversalSearch|null $universalSearch
 * @method static Builder|PdfLabel newModelQuery()
 * @method static Builder|PdfLabel newQuery()
 * @method static Builder|PdfLabel onlyTrashed()
 * @method static Builder|PdfLabel query()
 * @method static Builder|PdfLabel withTrashed()
 * @method static Builder|PdfLabel withoutTrashed()
 * @mixin Eloquent
 */
class PdfLabel extends Model
{
    use SoftDeletes;
    use HasSlug;

    use HasUniversalSearch;
    use HasFactory;

    protected $casts = [
        'data' => 'array',
    ];

    protected $attributes = [
        'data' => '{}',
    ];

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('code')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class);
    }

}
