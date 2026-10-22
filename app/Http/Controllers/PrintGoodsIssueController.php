<?php

namespace App\Http\Controllers;

use App\Models\GoodsIssue;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class PrintGoodsIssueController extends Controller
{
    public function __invoke(GoodsIssue $goodsIssue): PdfBuilder
    {
        Gate::authorize('print', $goodsIssue);

        $goodsIssue->load([
            'company',
            'warehouse',
            'warehouseAddress',
            'division',
            'project',
            'user',
            'goodsIssueItems.item',
            'statusLogs.user',
        ]);

        $viewData = [
            'goodsIssue' => $goodsIssue,
            'printedAt' => now(),
            'printedBy' => auth()->user(),
        ];

        $pageCount = $this->countPages($this->makePdf($goodsIssue, $viewData)->generatePdfContent());

        $builder = $this->makePdf($goodsIssue, [...$viewData, 'pageCount' => $pageCount])
            ->name($this->filename($goodsIssue));

        return request()->boolean('inline')
            ? $builder->inline()
            : $builder->download();
    }

    private function filename(GoodsIssue $goodsIssue): string
    {
        return 'GI_'.str_replace('/', '_', (string) $goodsIssue->number).'.pdf';
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function makePdf(GoodsIssue $goodsIssue, array $viewData): PdfBuilder
    {
        return pdf()
            ->view('pdf.goods-issue', $viewData)
            ->driver('dompdf')
            ->format('a4');
    }

    private function countPages(string $pdfContent): int
    {
        return max(preg_match_all('~/Type\s*/Page\b~', $pdfContent), 1);
    }
}
