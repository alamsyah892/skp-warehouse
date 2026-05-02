<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum GoodsIssueType: int
{
    case ISSUE = 1;
    case TRANSFER = 2;

    public function label(): string
    {
        return match ($this) {
            self::ISSUE => __('goods-issue.type.issue.label'),
            self::TRANSFER => __('goods-issue.type.transfer.label'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ISSUE => 'primary',
            self::TRANSFER => 'warning',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::ISSUE => Heroicon::OutlinedLinkSlash,
            self::TRANSFER => Heroicon::OutlinedShare,
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn(self $type): array => [
                (string) $type->value => $type->label(),
            ])
            ->toArray();
    }
}
