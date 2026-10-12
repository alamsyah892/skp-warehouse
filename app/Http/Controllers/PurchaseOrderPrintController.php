<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Support\IndonesianNumberToWords;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

abstract class PurchaseOrderPrintController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder): PdfBuilder
    {
        abort_unless($purchaseOrder->status === $this->printableStatus(), 404);

        Gate::authorize($this->printAbility(), $purchaseOrder);

        $purchaseOrder->load([
            'company',
            'vendor',
            'division',
            'project',
            'warehouse',
            'warehouseAddress',
            'purchaseRequests.user',
            'purchaseOrderItems.item',
        ]);

        $summary = PurchaseOrder::calculateOrderSummary(
            $purchaseOrder->purchaseOrderItems
                ->map(fn ($item): array => [
                    'qty' => $item->qty,
                    'price' => $item->price,
                ])
                ->all(),
            $purchaseOrder->discount,
            $purchaseOrder->tax_type,
            $purchaseOrder->tax_percentage,
            $purchaseOrder->rounding,
        );

        $viewData = [
            'purchaseOrder' => $purchaseOrder,
            'summary' => $summary,
            'terbilang' => IndonesianNumberToWords::rupiah($summary['grand_total']),
            'printedAt' => now(),
        ];

        $pageCount = $this->countPages($this->makePdf($viewData)->generatePdfContent());

        return $this->makePdf([...$viewData, 'pageCount' => $pageCount])
            ->name($this->filename($purchaseOrder))
            ->download();
    }

    abstract protected function printableStatus(): PurchaseOrderStatus;

    abstract protected function printAbility(): string;

    abstract protected function pdfView(): string;

    abstract protected function filenamePrefix(): string;

    private function filename(PurchaseOrder $purchaseOrder): string
    {
        return $this->filenamePrefix().str_replace('/', '_', (string) $purchaseOrder->number).'.pdf';
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function makePdf(array $viewData): PdfBuilder
    {
        return pdf()
            ->view($this->pdfView(), $viewData)
            ->driver('dompdf')
            ->format('a4');
    }

    private function countPages(string $pdfContent): int
    {
        return max(preg_match_all('~/Type\s*/Page\b~', $pdfContent), 1);
    }
}
