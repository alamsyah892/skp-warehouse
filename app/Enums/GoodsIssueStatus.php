<?php

namespace App\Enums;

use App\Models\Role;
use Filament\Support\Icons\Heroicon;

enum GoodsIssueStatus: int
{
    case ISSUED = 1;
    case CANCELED = 2;

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => __('goods-issue.status.issued.label'),
            self::CANCELED => __('goods-issue.status.canceled.label'),
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::ISSUED => __('goods-issue.status.issued.action_label'),
            self::CANCELED => __('goods-issue.status.canceled.action_label'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ISSUED => 'success',
            self::CANCELED => 'danger',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::ISSUED => Heroicon::OutlinedCheckCircle,
            self::CANCELED => Heroicon::OutlinedXCircle,
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::ISSUED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                ],
            ],
            self::CANCELED => [],
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn(self $status): array => [
                (string) $status->value => $status->label(),
            ])
            ->toArray();
    }
}
