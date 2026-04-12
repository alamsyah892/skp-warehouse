<?php

namespace App\Enums;

use App\Models\Role;
use Filament\Support\Icons\Heroicon;

enum PurchaseRequestStatus: int
{
    case DRAFT = 1;
    case CANCELED = 2;
    case REQUESTED = 3;
    case APPROVED = 4;
    case ORDERED = 5;
    case FINISHED = 6;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('purchase-request.status.draft.label'),
            self::CANCELED => __('purchase-request.status.canceled.label'),
            self::REQUESTED => __('purchase-request.status.requested.label'),
            self::APPROVED => __('purchase-request.status.approved.label'),
            self::ORDERED => __('purchase-request.status.ordered.label'),
            self::FINISHED => __('purchase-request.status.finished.label'),
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('purchase-request.status.draft.action_label'),
            self::CANCELED => __('purchase-request.status.canceled.action_label'),
            self::REQUESTED => __('purchase-request.status.requested.action_label'),
            self::APPROVED => __('purchase-request.status.approved.action_label'),
            self::ORDERED => __('purchase-request.status.ordered.action_label'),
            self::FINISHED => __('purchase-request.status.finished.action_label'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::CANCELED => 'danger',
            self::REQUESTED => 'warning',
            self::APPROVED => 'primary',
            self::ORDERED => 'info',
            self::FINISHED => 'success',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::DRAFT => Heroicon::OutlinedPencilSquare,
            self::CANCELED => Heroicon::OutlinedXCircle,
            self::REQUESTED => Heroicon::OutlinedClock,
            self::APPROVED => Heroicon::OutlinedInboxArrowDown,
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
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
                self::REQUESTED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
            ],

            self::CANCELED => [
                // self::REQUESTED->value => [
                //     Role::PROJECT_OWNER,
                //     Role::ADMINISTRATOR,
                //     Role::LOGISTIC,
                //     Role::LOGISTIC_MANAGER,
                //     Role::PURCHASING,
                //     Role::PURCHASING_MANAGER,
                // ],
            ],

            self::REQUESTED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::QUANTITY_SURVEYOR,
                    Role::AUDIT,
                    Role::AUDIT_MANAGER,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
                self::APPROVED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::QUANTITY_SURVEYOR,
                    Role::AUDIT,
                    Role::AUDIT_MANAGER,
                    Role::PURCHASING,
                    Role::PURCHASING_MANAGER,
                ],
            ],

            self::APPROVED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::QUANTITY_SURVEYOR,
                    Role::AUDIT,
                    Role::AUDIT_MANAGER,
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
                    Role::LOGISTIC,
                    Role::LOGISTIC_MANAGER,
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
                $status->value => $status->label()
            ])
            ->toArray()
        ;
    }
}