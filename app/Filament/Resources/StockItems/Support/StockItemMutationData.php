<?php

namespace App\Filament\Resources\StockItems\Support;

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsReceiveStatus;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceiveItem;
use Carbon\CarbonImmutable;

class StockItemMutationData
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $summaryCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected static array $yearOptionsCache = [];

    /**
     * @return array<string, string>
     */
    public static function getYearOptions(GoodsReceiveItem $record): array
    {
        $cacheKey = static::getCacheKey($record);

        if (array_key_exists($cacheKey, static::$yearOptionsCache)) {
            return static::$yearOptionsCache[$cacheKey];
        }

        $years = static::baseGoodsReceiveQuery($record)
            ->pluck('goods_receives.created_at')
            ->merge(static::baseGoodsIssueQuery($record)->pluck('goods_issues.created_at'))
            ->filter()
            ->map(fn (mixed $date): string => (string) CarbonImmutable::parse($date)->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->mapWithKeys(fn (string $year): array => [$year => $year])
            ->all();

        return static::$yearOptionsCache[$cacheKey] = $years;
    }

    /**
     * @return array{
     *     period_label: string,
     *     opening_balance: float,
     *     total_received: float,
     *     total_issued: float,
     *     ending_balance: float,
     *     mutations: array<int, array{
     *         transaction_date: string,
     *         document_type: string,
     *         document_type_label: string,
     *         document_number: string,
     *         description: string,
     *         qty_in: float,
     *         qty_out: float,
     *         balance: float
     *     }>
     * }
     */
    public static function getSummary(GoodsReceiveItem $record, ?int $year = null, ?int $month = null): array
    {
        $period = static::resolvePeriod($year, $month);
        $cacheKey = implode('|', [
            static::getCacheKey($record),
            ($period['start'] ?? null)?->format('Y-m-d H:i:s') ?? 'all',
            ($period['end'] ?? null)?->format('Y-m-d H:i:s') ?? 'all',
        ]);

        if (array_key_exists($cacheKey, static::$summaryCache)) {
            return static::$summaryCache[$cacheKey];
        }

        $openingBalance = $period === null
            ? 0.0
            : round(
                static::sumGoodsReceivesBefore($record, $period['start']) - static::sumGoodsIssuesBefore($record, $period['start']),
                2,
            );

        $mutations = static::getMutations($record, $period, $openingBalance);
        $totalReceived = round(collect($mutations)->sum('qty_in'), 2);
        $totalIssued = round(collect($mutations)->sum('qty_out'), 2);

        return static::$summaryCache[$cacheKey] = [
            'period_label' => $period['label'] ?? __('stock-item.mutation.period.all'),
            'opening_balance' => $openingBalance,
            'total_received' => $totalReceived,
            'total_issued' => $totalIssued,
            'ending_balance' => round($openingBalance + $totalReceived - $totalIssued, 2),
            'mutations' => $mutations,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, label: string}|null  $period
     * @return array<int, array{
     *     transaction_date: string,
     *     document_type: string,
     *     document_type_label: string,
     *     document_number: string,
     *     description: string,
     *     qty_in: float,
     *     qty_out: float,
     *     balance: float
     * }>
     */
    protected static function getMutations(GoodsReceiveItem $record, ?array $period, float $openingBalance): array
    {
        $goodsReceives = static::baseGoodsReceiveQuery($record)
            ->selectRaw('
                goods_receives.id as document_id,
                goods_receives.created_at as transaction_at,
                goods_receives.number as document_number,
                goods_receives.description as header_description,
                goods_receive_items.description as line_description,
                goods_receive_items.qty as quantity,
                goods_receive_items.sort as line_sort
            ')
            ->when(
                $period,
                fn ($query) => $query->whereBetween('goods_receives.created_at', [$period['start'], $period['end']]),
            )
            ->get()
            ->map(fn (object $row): array => [
                'transaction_at' => CarbonImmutable::parse($row->transaction_at),
                'document_type_order' => 1,
                'document_id' => (int) $row->document_id,
                'line_sort' => (int) $row->line_sort,
                'transaction_date' => CarbonImmutable::parse($row->transaction_at)->format('d M Y'),
                'document_type' => 'GR',
                'document_type_label' => __('goods-receive.model.label'),
                'document_number' => (string) $row->document_number,
                'description' => static::combineDescription(
                    (string) ($row->header_description ?? ''),
                    (string) ($row->line_description ?? ''),
                ),
                'qty_in' => (float) $row->quantity,
                'qty_out' => 0.0,
            ]);

        $goodsIssues = static::baseGoodsIssueQuery($record)
            ->selectRaw('
                goods_issues.id as document_id,
                goods_issues.created_at as transaction_at,
                goods_issues.number as document_number,
                goods_issues.description as header_description,
                goods_issue_items.description as line_description,
                goods_issue_items.qty as quantity,
                goods_issue_items.sort as line_sort
            ')
            ->when(
                $period,
                fn ($query) => $query->whereBetween('goods_issues.created_at', [$period['start'], $period['end']]),
            )
            ->get()
            ->map(fn (object $row): array => [
                'transaction_at' => CarbonImmutable::parse($row->transaction_at),
                'document_type_order' => 2,
                'document_id' => (int) $row->document_id,
                'line_sort' => (int) $row->line_sort,
                'transaction_date' => CarbonImmutable::parse($row->transaction_at)->format('d M Y'),
                'document_type' => 'GI',
                'document_type_label' => __('goods-issue.model.label'),
                'document_number' => (string) $row->document_number,
                'description' => static::combineDescription(
                    (string) ($row->header_description ?? ''),
                    (string) ($row->line_description ?? ''),
                ),
                'qty_in' => 0.0,
                'qty_out' => (float) $row->quantity,
            ]);

        $mutations = $goodsReceives
            ->merge($goodsIssues)
            ->sort(function (array $left, array $right): int {
                $compare = $left['transaction_at']->getTimestamp() <=> $right['transaction_at']->getTimestamp();

                if ($compare !== 0) {
                    return $compare;
                }

                $compare = $left['document_type_order'] <=> $right['document_type_order'];

                if ($compare !== 0) {
                    return $compare;
                }

                $compare = $left['document_id'] <=> $right['document_id'];

                if ($compare !== 0) {
                    return $compare;
                }

                return $left['line_sort'] <=> $right['line_sort'];
            })
            ->values();

        $runningBalance = $openingBalance;

        return $mutations
            ->map(function (array $mutation) use (&$runningBalance): array {
                $runningBalance = round($runningBalance + $mutation['qty_in'] - $mutation['qty_out'], 2);

                unset($mutation['transaction_at'], $mutation['document_type_order'], $mutation['document_id'], $mutation['line_sort']);

                $mutation['balance'] = $runningBalance;

                return $mutation;
            })
            ->all();
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, label: string}|null
     */
    protected static function resolvePeriod(?int $year, ?int $month): ?array
    {
        if (($year === null) || ($month === null) || ($month < 1) || ($month > 12)) {
            return null;
        }

        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();

        return [
            'start' => $start,
            'end' => $start->endOfMonth(),
            'label' => $start->translatedFormat('F Y'),
        ];
    }

    protected static function sumGoodsReceivesBefore(GoodsReceiveItem $record, CarbonImmutable $date): float
    {
        return (float) static::baseGoodsReceiveQuery($record)
            ->where('goods_receives.created_at', '<', $date)
            ->sum('goods_receive_items.qty');
    }

    protected static function sumGoodsIssuesBefore(GoodsReceiveItem $record, CarbonImmutable $date): float
    {
        return (float) static::baseGoodsIssueQuery($record)
            ->where('goods_issues.created_at', '<', $date)
            ->sum('goods_issue_items.qty');
    }

    protected static function baseGoodsReceiveQuery(GoodsReceiveItem $record)
    {
        return GoodsReceiveItem::query()
            ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
            ->whereIn('goods_receives.status', [
                GoodsReceiveStatus::RECEIVED->value,
                GoodsReceiveStatus::CONFIRMED->value,
            ])
            ->where('goods_receive_items.item_id', $record->item_id)
            ->where('goods_receives.warehouse_id', $record->warehouse_id)
            ->where('goods_receives.company_id', $record->company_id)
            ->where('goods_receives.division_id', $record->division_id)
            ->where('goods_receives.project_id', $record->project_id);
    }

    protected static function baseGoodsIssueQuery(GoodsReceiveItem $record)
    {
        return GoodsIssueItem::query()
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->where('goods_issues.status', GoodsIssueStatus::ISSUED->value)
            ->where('goods_issue_items.item_id', $record->item_id)
            ->where('goods_issues.warehouse_id', $record->warehouse_id)
            ->where('goods_issues.company_id', $record->company_id)
            ->where('goods_issues.division_id', $record->division_id)
            ->where('goods_issues.project_id', $record->project_id);
    }

    protected static function combineDescription(string $headerDescription, string $lineDescription): string
    {
        return collect([
            blank($headerDescription) ? null : trim($headerDescription),
            blank($lineDescription) ? null : trim($lineDescription),
        ])
            ->filter()
            ->unique()
            ->join("\n");
    }

    protected static function getCacheKey(GoodsReceiveItem $record): string
    {
        return implode('|', [
            (int) $record->item_id,
            (int) $record->warehouse_id,
            (int) $record->company_id,
            (int) $record->division_id,
            (int) $record->project_id,
        ]);
    }
}
