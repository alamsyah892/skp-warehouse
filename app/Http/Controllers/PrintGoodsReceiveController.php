<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceive;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class PrintGoodsReceiveController extends Controller
{
    public function __invoke(GoodsReceive $goodsReceive): PdfBuilder
    {
        Gate::authorize('print', $goodsReceive);

        $goodsReceive->load([
            'company',
            'warehouse',
            'warehouseAddress',
            'division',
            'project',
            'user',
            'purchaseOrder.vendor',
            'goodsReceiveItems.item',
            'goodsReceiveItems.purchaseOrderItem',
            'statusLogs.user',
        ]);

        $viewData = [
            'goodsReceive' => $goodsReceive,
            'printedAt' => now(),
            'printedBy' => auth()->user(),
        ];

        $pageCount = $this->countPages($this->makePdf($goodsReceive, $viewData)->generatePdfContent());

        $builder = $this->makePdf($goodsReceive, [...$viewData, 'pageCount' => $pageCount])
            ->name($this->filename($goodsReceive));

        return request()->boolean('inline')
            ? $builder->inline()
            : $builder->download();
    }

    private function filename(GoodsReceive $goodsReceive): string
    {
        return 'GR_'.str_replace('/', '_', (string) $goodsReceive->number).'.pdf';
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function makePdf(GoodsReceive $goodsReceive, array $viewData): PdfBuilder
    {
        return pdf()
            ->view('pdf.goods-receive', $viewData)
            ->driver('dompdf')
            ->format('a4');
    }

    private function countPages(string $pdfContent): int
    {
        return max(preg_match_all('~/Type\s*/Page\b~', $pdfContent), 1);
    }
}
