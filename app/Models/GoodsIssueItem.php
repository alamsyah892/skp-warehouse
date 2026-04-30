<?php

namespace App\Models;

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsReceiveStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsIssueItem extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsIssueItemFactory> */
    use HasFactory;
    use LogsAllFillable, DefaultEmptyString;

    protected $fillable = [
        'goods_issue_id',
        'item_id',
        'qty',
        'description',
        'sort',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<int, array{item: Item, received_qty: float, issued_qty: float, available_qty: float}>
     */
    public static function getAvailableStockByHeader(array $header, ?int $exceptGoodsIssueId = null): array
    {
        $normalizedHeader = static::normalizeHeader($header);

        if ($normalizedHeader === null) {
            return [];
        }

        static $cache = [];

        $cacheKey = md5(json_encode([
            'header' => $normalizedHeader,
            'except_goods_issue_id' => $exceptGoodsIssueId,
        ], JSON_THROW_ON_ERROR));

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $receivedQuantities = GoodsReceiveItem::query()
            ->selectRaw('goods_receive_items.item_id, coalesce(sum(goods_receive_items.qty), 0) as total_qty')
            ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
            ->where('goods_receives.status', GoodsReceiveStatus::RECEIVED->value)
            ->where('goods_receives.warehouse_id', $normalizedHeader['warehouse_id'])
            ->where('goods_receives.company_id', $normalizedHeader['company_id'])
            ->where('goods_receives.division_id', $normalizedHeader['division_id'])
            ->where('goods_receives.project_id', $normalizedHeader['project_id'])
            ->groupBy('goods_receive_items.item_id')
            ->pluck('total_qty', 'goods_receive_items.item_id')
            ->map(fn(mixed $qty): float => (float) $qty)
            ->all();

        $issuedQuantities = static::query()
            ->selectRaw('goods_issue_items.item_id, coalesce(sum(goods_issue_items.qty), 0) as total_qty')
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->where('goods_issues.status', GoodsIssueStatus::ISSUED->value)
            ->where('goods_issues.warehouse_id', $normalizedHeader['warehouse_id'])
            ->where('goods_issues.company_id', $normalizedHeader['company_id'])
            ->where('goods_issues.division_id', $normalizedHeader['division_id'])
            ->where('goods_issues.project_id', $normalizedHeader['project_id'])
            ->when(
                $exceptGoodsIssueId,
                fn(Builder $query) => $query->where('goods_issues.id', '!=', $exceptGoodsIssueId),
            )
            ->groupBy('goods_issue_items.item_id')
            ->pluck('total_qty', 'goods_issue_items.item_id')
            ->map(fn(mixed $qty): float => (float) $qty)
            ->all();

        $itemIds = collect(array_keys($receivedQuantities))
            ->merge(array_keys($issuedQuantities))
            ->map(fn(mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'code', 'name', 'unit'])
            ->keyBy('id');

        return $cache[$cacheKey] = collect($itemIds)
            ->mapWithKeys(function (int $itemId) use ($items, $receivedQuantities, $issuedQuantities): array {
                /** @var Item|null $item */
                $item = $items->get($itemId);

                if (!$item) {
                    return [];
                }

                $receivedQty = (float) ($receivedQuantities[$itemId] ?? 0);
                $issuedQty = (float) ($issuedQuantities[$itemId] ?? 0);
                $availableQty = max(round($receivedQty - $issuedQty, 2), 0.0);

                if ($availableQty <= 0) {
                    return [];
                }

                return [
                    $itemId => [
                        'item' => $item,
                        'received_qty' => $receivedQty,
                        'issued_qty' => $issuedQty,
                        'available_qty' => $availableQty,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<int, string>
     */
    public static function getSelectableOptions(array $header, ?int $exceptGoodsIssueId = null, ?string $search = null): array
    {
        $stocks = static::getAvailableStockByHeader($header, $exceptGoodsIssueId);

        return collect($stocks)
            ->filter(function (array $stock) use ($search): bool {
                if (blank($search)) {
                    return true;
                }

                $item = $stock['item'];
                $keyword = mb_strtolower(trim((string) $search));

                return str_contains(mb_strtolower($item->code), $keyword)
                    || str_contains(mb_strtolower($item->name), $keyword);
            })
            ->mapWithKeys(fn(array $stock, int $itemId): array => [
                $itemId => "{$stock['item']->code} | {$stock['item']->name}",
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array{item: Item, received_qty: float, issued_qty: float, available_qty: float}|null
     */
    public static function getAvailabilityDetail(array $header, int $itemId, ?int $exceptGoodsIssueId = null): ?array
    {
        return static::getAvailableStockByHeader($header, $exceptGoodsIssueId)[$itemId] ?? null;
    }

    /**
     * @param  array<string, mixed>  $header
     */
    public static function getAvailableQtyForItem(array $header, int $itemId, ?int $exceptGoodsIssueId = null): float
    {
        return (float) (static::getAvailabilityDetail($header, $itemId, $exceptGoodsIssueId)['available_qty'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array{warehouse_id: int, company_id: int, division_id: int, project_id: int}|null
     */
    protected static function normalizeHeader(array $header): ?array
    {
        $normalized = [
            'warehouse_id' => (int) ($header['warehouse_id'] ?? 0),
            'company_id' => (int) ($header['company_id'] ?? 0),
            'division_id' => (int) ($header['division_id'] ?? 0),
            'project_id' => (int) ($header['project_id'] ?? 0),
        ];

        return collect($normalized)->every(fn(int $value): bool => $value > 0)
            ? $normalized
            : null;
    }
}
