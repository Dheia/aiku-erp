<?php

namespace App\Exports\Accounting;

use App\Models\Accounting\Invoice;
use App\Models\Accounting\Payment;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceExport implements FromQuery, WithMapping, ShouldAutoSize, WithHeadings
{
    public function query(): Relation|\Illuminate\Database\Eloquent\Builder|Payment|Builder
    {
        return Invoice::query();
    }

    /** @var Invoice $row */
    public function map($row): array
    {
        return [
            $row->id,
            $row->slug,
            $row->type,
            $row->shop->name,
            $row->customer->name,
            $row->currency->name,
            $row->exchange,
            $row->net,
            $row->total,
            $row->payment,
            $row->paid_at
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Slug',
            'Type',
            'Shop Name',
            'Customer Name',
            'Currency Name',
            'Exchange',
            'Net',
            'Total',
            'Payment',
            'Paid At'
        ];
    }
}
