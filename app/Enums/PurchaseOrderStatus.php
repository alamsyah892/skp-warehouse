<?php

namespace App\Enums;

use App\Models\Role;
use Filament\Support\Icons\Heroicon;

enum PurchaseOrderStatus: int
{
    case DRAFT = 1;
    case CANCELED = 2;
    case ORDERED = 3;
    case FINISHED = 4;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('purchase-order.status.draft.label'),
            self::CANCELED => __('purchase-order.status.canceled.label'),
            self::ORDERED => __('purchase-order.status.ordered.label'),
            self::FINISHED => __('purchase-order.status.finished.label'),
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('purchase-order.status.draft.action_label'),
            self::CANCELED => __('purchase-order.status.canceled.action_label'),
            self::ORDERED => __('purchase-order.status.ordered.action_label'),
            self::FINISHED => __('purchase-order.status.finished.action_label'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::CANCELED => 'danger',
            self::ORDERED => 'info',
            self::FINISHED => 'success',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::DRAFT => Heroicon::OutlinedPencilSquare,
            self::CANCELED => Heroicon::OutlinedXCircle,
            self::ORDERED => Heroicon::OutlinedShoppingCart,
            self::FINISHED => Heroicon::OutlinedCheckCircle,
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::DRAFT => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
                self::ORDERED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
            ],
            self::CANCELED => [
                self::DRAFT->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
                self::ORDERED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
            ],
            self::ORDERED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
                self::FINISHED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
            ],
            self::FINISHED => [],
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn($status) => [
                (string) $status->value => $status->label()
            ])
            ->toArray()
        ;
    }
}
