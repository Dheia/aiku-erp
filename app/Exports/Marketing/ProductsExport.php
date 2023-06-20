<?php

namespace App\Exports\Marketing;

use App\Models\Market\Product;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithMapping, ShouldAutoSize, WithHeadings
{
    public function query(): Relation|\Illuminate\Database\Eloquent\Builder|Product|Builder
    {
        return Product::query();
    }

    /** @var Product $row */
    public function map($row): array
    {
        return [
            $row->id,
            $row->slug,
            $row->type,
            $row->shop->name,
            $row->state,
            $row->status,
            $row->units,
            $row->price,
            $row->rrp,
            $row->available,
            $row->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Slug',
            'Type',
            'Shop Name',
            'State',
            'Status',
            'Units',
            'Price',
            'RRP',
            'Available',
            'Created At'
        ];
    }
}
