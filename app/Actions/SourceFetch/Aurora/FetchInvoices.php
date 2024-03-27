<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Wed, 19 Oct 2022 19:32:35 British Summer Time, Sheffield, UK
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Actions\SourceFetch\Aurora;

use App\Actions\Accounting\Invoice\StoreInvoice;
use App\Actions\Accounting\Invoice\UpdateInvoice;
use App\Actions\Helpers\Address\StoreHistoricAddress;
use App\Actions\Helpers\Address\UpdateHistoricAddressToModel;
use App\Models\Accounting\Invoice;
use App\Services\Organisation\SourceOrganisationService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class FetchInvoices extends FetchAction
{
    public string $commandSignature = 'fetch:invoices {organisations?*} {--s|source_id=} {--S|shop= : Shop slug}  {--N|only_new : Fetch only new} {--w|with=* : Accepted values: transactions} {--d|db_suffix=} {--r|reset}';

    public function handle(SourceOrganisationService $organisationSource, int $organisationSourceId): ?Invoice
    {
        if ($invoiceData = $organisationSource->fetchInvoice($organisationSourceId)) {
            if ($invoice = Invoice::withTrashed()->where('source_id', $invoiceData['invoice']['source_id'])
                ->first()) {
                UpdateInvoice::make()->action(
                    invoice: $invoice,
                    modelData: $invoiceData['invoice'],
                    hydratorsDelay: 60,
                );

                $currentBillingAddress = $invoice->getAddress('billing');

                if ($currentBillingAddress->checksum != $invoiceData['invoice']['billing_address']->getChecksum()) {
                    $billingAddress = StoreHistoricAddress::run($invoiceData['invoice']['billing_address']);
                    UpdateHistoricAddressToModel::run($invoice, $currentBillingAddress, $billingAddress, ['scope' => 'billing']);
                }

                if (in_array('transactions', $this->with)) {
                    $this->fetchInvoiceTransactions($organisationSource, $invoice);
                }

                $this->updateAurora($invoice);

                return $invoice;
            } else {
                if ($invoiceData['invoice']) {
                    if ($invoiceData['invoice']['data']['foot_note'] == '') {
                        unset($invoiceData['invoice']['data']['foot_note']);
                    }

                    $invoice = StoreInvoice::make()->action(
                        parent: $invoiceData['parent'],
                        modelData: $invoiceData['invoice'],
                        hydratorsDelay: $this->hydrateDelay,
                        strict:false
                    );
                    if (in_array('transactions', $this->with)) {
                        $this->fetchInvoiceTransactions($organisationSource, $invoice);
                    }


                    $this->updateAurora($invoice);

                    return $invoice;
                }
                print "Warning order $organisationSourceId do not have customer\n";
            }
        }

        return null;
    }

    public function updateAurora(Invoice $invoice): void
    {
        $sourceData = explode(':', $invoice->source_id);
        DB::connection('aurora')->table('Invoice Dimension')
            ->where('Invoice Key', $sourceData[1])
            ->update(['aiku_id' => $invoice->id]);
    }

    private function fetchInvoiceTransactions($organisationSource, Invoice $invoice): void
    {
        $transactionsToDelete = $invoice->invoiceTransactions()->pluck('source_id', 'id')->all();

        foreach (
            DB::connection('aurora')
                ->table('Order Transaction Fact')
                ->select('Order Transaction Fact Key')
                ->where('Invoice Key', $invoice->source_id)
                ->get() as $auroraData
        ) {
            $transactionsToDelete = array_diff($transactionsToDelete, [$auroraData->{'Order Transaction Fact Key'}]);
            fetchInvoiceTransactions::run($organisationSource, $auroraData->{'Order Transaction Fact Key'}, $invoice);
        }
        $invoice->invoiceTransactions()->whereIn('id', array_keys($transactionsToDelete))->delete();
    }

    public function getModelsQuery(): Builder
    {
        $query = DB::connection('aurora')
            ->table('Invoice Dimension')
            ->select('Invoice Key as source_id')
            ->orderBy('Invoice Date');

        if ($this->shop) {
            $sourceData = explode(':', $this->shop->source_id);
            $query->where('Invoice Store Key', $sourceData[1]);
        }
        if ($this->onlyNew) {
            $query->whereNull('aiku_id');
        }

        return $query;
    }

    public function count(): ?int
    {
        $query = DB::connection('aurora')->table('Invoice Dimension');
        if ($this->onlyNew) {
            $query->whereNull('aiku_id');
        }
        if ($this->shop) {
            $sourceData = explode(':', $this->shop->source_id);
            $query->where('Invoice Store Key', $sourceData[1]);
        }
        return $query->count();
    }

    public function reset(): void
    {
        DB::connection('aurora')->table('Invoice Dimension')->update(['aiku_id' => null]);
    }
}
