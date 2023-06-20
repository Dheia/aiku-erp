<?php
/*
 * Author: Artha <artha@aw-advantage.com>
 * Created: Mon, 17 Apr 2023 11:42:36 Central Indonesia Time, Sanur, Bali, Indonesia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Models\Procurement;

use App\Enums\Procurement\PurchaseOrder\PurchaseOrderStateEnum;
use App\Enums\Procurement\PurchaseOrder\PurchaseOrderStatusEnum;
use App\Models\Assets\Currency;
use App\Models\Helpers\Address;
use App\Models\Traits\HasHistory;
use App\Models\Traits\HasTenantAddress;
use App\Models\Traits\UsesGroupConnection;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * App\Models\Procurement\PurchaseOrder
 *
 * @property int $id
 * @property string $slug
 * @property int $provider_id
 * @property string $provider_type
 * @property string $number
 * @property array $data
 * @property PurchaseOrderStateEnum $state
 * @property PurchaseOrderStatusEnum $status
 * @property string $date latest relevant date
 * @property string|null $submitted_at
 * @property string|null $confirmed_at
 * @property string|null $manufactured_at
 * @property string|null $dispatched_at
 * @property string|null $received_at
 * @property string|null $checked_at
 * @property string|null $settled_at
 * @property string|null $cancelled_at
 * @property int $currency_id
 * @property string $exchange
 * @property int $number_of_items
 * @property float|null $gross_weight
 * @property float|null $net_weight
 * @property string|null $cost_items
 * @property string|null $cost_extra
 * @property string|null $cost_shipping
 * @property string|null $cost_duties
 * @property string $cost_tax
 * @property string $cost_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $source_id
 * @property-read Collection<int, Address> $addresses
 * @property-read Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read array $es_audits
 * @property-read Collection<int, \App\Models\Procurement\PurchaseOrderItem> $items
 * @property-read Model|\Eloquent $provider
 * @property-read Currency $currency
 * @property-read Collection<int, \App\Models\Procurement\SupplierDelivery> $supplierDeliveries
 * @method static \Database\Factories\Procurement\PurchaseOrderFactory factory($count = null, $state = [])
 * @method static Builder|PurchaseOrder newModelQuery()
 * @method static Builder|PurchaseOrder newQuery()
 * @method static Builder|PurchaseOrder onlyTrashed()
 * @method static Builder|PurchaseOrder query()
 * @method static Builder|PurchaseOrder withTrashed()
 * @method static Builder|PurchaseOrder withoutTrashed()
 * @mixin Eloquent
 */
class PurchaseOrder extends Model implements Auditable
{
    use UsesGroupConnection;
    use SoftDeletes;
    use HasTenantAddress;
    use HasSlug;
    use HasFactory;
    use HasHistory;


    protected $casts = [
        'data'   => 'array',
        'state'  => PurchaseOrderStateEnum::class,
        'status' => PurchaseOrderStatusEnum::class
    ];

    protected $attributes = [
        'data' => '{}',
    ];

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('number')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function provider(): MorphTo
    {
        return $this->morphTo();
    }

    public function supplierDeliveries(): BelongsToMany
    {
        return $this->belongsToMany(SupplierDelivery::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
