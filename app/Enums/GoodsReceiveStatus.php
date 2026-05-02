<?php

namespace App\Enums;

use App\Models\Role;
use Filament\Support\Icons\Heroicon;

enum GoodsReceiveStatus: int
{
    case RECEIVED = 1;
    case CANCELED = 2;
    case RETURNED = 3;
    case CONFIRMED = 4;

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => __('goods-receive.status.received.label'),
            self::RETURNED => __('goods-receive.status.returned.label'),
            self::CANCELED => __('goods-receive.status.canceled.label'),
            self::CONFIRMED => __('goods-receive.status.confirmed.label'),
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::RECEIVED => __('goods-receive.status.received.action_label'),
            self::RETURNED => __('goods-receive.status.returned.action_label'),
            self::CANCELED => __('goods-receive.status.canceled.action_label'),
            self::CONFIRMED => __('goods-receive.status.confirmed.action_label'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RECEIVED => 'info',
            self::CANCELED => 'danger',
            self::RETURNED => 'warning',
            self::CONFIRMED => 'success',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::RECEIVED => Heroicon::OutlinedInboxArrowDown,
            self::RETURNED => Heroicon::OutlinedReceiptRefund,
                // self::RETURNED => Heroicon::OutlinedArrowUturnLeft,
            self::CANCELED => Heroicon::OutlinedXCircle,
            self::CONFIRMED => Heroicon::OutlinedCheckCircle,
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::RECEIVED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
                self::RETURNED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
                self::CONFIRMED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                    Role::FINANCE,
                    Role::FINANCE_MANAGER,
                ],
            ],
            self::CANCELED => [
                self::RECEIVED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
            ],
            self::RETURNED => [
                self::RECEIVED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
            ],
            self::CONFIRMED => [
                self::RECEIVED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
            ],
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn(self $status): array => [
                (string) $status->value => $status->label(),
            ])
            ->toArray()
        ;
    }
}

