<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 20 Jul 2023 12:13:48 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Models\Fulfilment;

use App\Enums\Fulfilment\StoredItem\StoredItemStateEnum;
use App\Enums\Fulfilment\StoredItem\StoredItemStatusEnum;
use App\Enums\Fulfilment\StoredItem\StoredItemTypeEnum;
use App\Models\CRM\Customer;
use App\Models\Traits\HasHistory;
use App\Models\Traits\HasUniversalSearch;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\Inventory\Location;

/**
 * App\Models\Fulfilment\StoredItem
 *
 * @property int $id
 * @property string $slug
 * @property string $reference
 * @property string $code
 * @property StoredItemStatusEnum $status
 * @property StoredItemStateEnum $state
 * @property StoredItemTypeEnum $type
 * @property int $customer_id
 * @property int $location_id
 * @property string $notes
 * @property bool $return_requested
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $received_at
 * @property string|null $booked_in_at
 * @property string|null $settled_at
 * @property array $data
 * @property Carbon|null $deleted_at
 * @property int|null $source_id
 * @property-read \App\Models\Search\UniversalSearch|null $universalSearch
 * @method static Builder|StoredItem newModelQuery()
 * @method static Builder|StoredItem newQuery()
 * @method static Builder|StoredItem onlyTrashed()
 * @method static Builder|StoredItem query()
 * @method static Builder|StoredItem withTrashed()
 * @method static Builder|StoredItem withoutTrashed()
 * @mixin Eloquent
 */
class StoredItem extends Model implements Auditable
{
    use UsesTenantConnection;
    use HasUniversalSearch;
    use SoftDeletes;
    use HasSlug;
    use HasHistory;

    protected $casts = [
        'data'   => 'array',
        'state'  => StoredItemStateEnum::class,
        'status' => StoredItemStatusEnum::class,
        'type'   => StoredItemTypeEnum::class
    ];

    protected $attributes = [
        'data' => '{}',
        'notes' => '',
    ];

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('reference')
            ->doNotGenerateSlugsOnUpdate()
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
