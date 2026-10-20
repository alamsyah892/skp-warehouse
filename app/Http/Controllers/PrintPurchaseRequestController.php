<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class PrintPurchaseRequestController extends Controller
{
    public function __invoke(PurchaseRequest $purchaseRequest): PdfBuilder
    {
        Gate::authorize('print', $purchaseRequest);

        $purchaseRequest->load([
            'company',
            'warehouse',
            'warehouseAddress',
            'division',
            'project',
            'user',
            'purchaseRequestItems.item',
            'statusLogs.user',
        ]);

        $viewData = [
            'purchaseRequest' => $purchaseRequest,
            'printedAt' => now(),
            'printedBy' => auth()->user(),
        ];

        $pageCount = $this->countPages($this->makePdf($purchaseRequest, $viewData)->generatePdfContent());

        $builder = $this->makePdf($purchaseRequest, [...$viewData, 'pageCount' => $pageCount])
            ->name($this->filename($purchaseRequest));

        return request()->boolean('inline')
            ? $builder->inline()
            : $builder->download();
    }

    private function filename(PurchaseRequest $purchaseRequest): string
    {
        return 'PR_'.str_replace('/', '_', (string) $purchaseRequest->number).'.pdf';
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function makePdf(PurchaseRequest $purchaseRequest, array $viewData): PdfBuilder
    {
        return pdf()
            ->view('pdf.purchase-request', $viewData)
            ->driver('dompdf')
            ->format('a4');
    }

    private function countPages(string $pdfContent): int
    {
        return max(preg_match_all('~/Type\s*/Page\b~', $pdfContent), 1);
    }
}
