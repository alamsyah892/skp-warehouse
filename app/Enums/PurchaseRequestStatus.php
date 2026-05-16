<?php

namespace App\Enums;

use App\Models\Role;
use Filament\Support\Icons\Heroicon;

enum PurchaseRequestStatus: int
{
    case DRAFT = 1;
    case CANCELED = 2;
    case REQUESTED = 3;
    case CHECKED = 7; // 4
    case APPROVED = 8; // 5
    case REVIEWED = 4; // 6
    case ORDERED = 5;  // 7
    case FINISHED = 6; // 8

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('purchase-request.status.draft.label'),
            self::CANCELED => __('purchase-request.status.canceled.label'),
            self::REQUESTED => __('purchase-request.status.requested.label'),
            self::CHECKED => __('purchase-request.status.checked.label'),
            self::APPROVED => __('purchase-request.status.approved.label'),
            self::REVIEWED => __('purchase-request.status.reviewed.label'),
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
            self::CHECKED => __('purchase-request.status.checked.action_label'),
            self::APPROVED => __('purchase-request.status.approved.action_label'),
            self::REVIEWED => __('purchase-request.status.reviewed.action_label'),
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
            self::CHECKED => 'primary',
            self::APPROVED => 'primary',
            self::REVIEWED => 'primary',
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
            self::CHECKED => Heroicon::OutlinedMagnifyingGlassCircle,
            self::APPROVED => Heroicon::OutlinedCheck,
            self::REVIEWED => Heroicon::OutlinedEye,
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
                self::DRAFT->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
                self::REQUESTED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
                self::REVIEWED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
                self::ORDERED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
            ],

            self::REQUESTED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::QUANTITY_SURVEYOR,
                ],
                self::CHECKED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::QUANTITY_SURVEYOR,
                ],
            ],

            self::CHECKED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
                self::APPROVED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                ],
            ],

            self::APPROVED => [
                self::CANCELED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::AUDIT,
                    Role::AUDIT_MANAGER,
                ],
                self::REVIEWED->value => [
                    Role::PROJECT_OWNER,
                    Role::ADMINISTRATOR,
                    Role::AUDIT,
                    Role::AUDIT_MANAGER,
                ],
            ],

            self::REVIEWED => [
                self::CANCELED->value => [
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

            self::FINISHED => [
                self::ORDERED->value => [
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
            ->mapWithKeys(fn($status) => [
                $status->value => $status->label()
            ])
            ->toArray()
        ;
    }
}