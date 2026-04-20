<?php

namespace App\Enums;

use App\Models\Role;
use Filament\Support\Icons\Heroicon;

enum GoodsReceiveStatus: int
{
    case RECEIVED = 1;
    case RETURNED = 2;
    case CANCELED = 3;

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => __('goods-receive.status.received.label'),
            self::RETURNED => __('goods-receive.status.returned.label'),
            self::CANCELED => __('goods-receive.status.canceled.label'),
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::RECEIVED => __('goods-receive.status.received.action_label'),
            self::RETURNED => __('goods-receive.status.returned.action_label'),
            self::CANCELED => __('goods-receive.status.canceled.action_label'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RECEIVED => 'success',
            self::RETURNED => 'warning',
            self::CANCELED => 'danger',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::RECEIVED => Heroicon::OutlinedInboxArrowDown,
            self::RETURNED => Heroicon::OutlinedArrowUturnLeft,
            self::CANCELED => Heroicon::OutlinedXCircle,
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::RECEIVED => [
                self::RETURNED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                ],
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                ],
            ],
            self::RETURNED => [],
            self::CANCELED => [],
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [
                (string) $status->value => $status->label(),
            ])
            ->toArray();
    }
}

