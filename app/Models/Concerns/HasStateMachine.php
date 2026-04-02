<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasStateMachine
{
    protected ?array $cachedTransitions = null;


    /**
     * Abstract
     */
    abstract protected function getStatusField(): string;

    abstract protected function getStatusEnumClass(): string;

    abstract protected function setStatusLog($newStatus, $oldStatus = null, string $note = '');


    /**
     * Core
     */
    public function changeStatus($newStatus, ?string $note = ''): void
    {
        $newStatus = $this->normalizeStatus($newStatus);

        if ($newStatus === $this->getStatus()) {
            return;
        }

        if (!$this->canChangeStatusTo($newStatus)) {
            throw new \RuntimeException("Invalid status transition");
        }

        DB::transaction(function () use ($newStatus, $note) {
            $field = $this->getStatusField();

            $oldStatus = $this->getStatus();

            $updated = static::where('id', $this->id)
                ->where($field, $oldStatus->value)
                ->update([
                    $field => $newStatus->value,
                ])
            ;

            if ($updated === 0) {
                throw new \RuntimeException("Status has been changed by another user.");
            }

            $this->{$field} = $newStatus;

            $this->setStatusLog($newStatus, $oldStatus, $note);
        });
    }

    public function canChangeStatusTo($newStatus, $user = null): bool
    {
        $newStatus = $this->normalizeStatus($newStatus);

        if ($newStatus === $this->getStatus()) {
            return true;
        }

        $user ??= auth()->user();

        if (!$user || app()->runningInConsole()) {
            return false;
        }

        $flow = $this->getStatusTransitions();

        if (!array_key_exists($newStatus->value, $flow)) {
            return false;
        }

        return $this->canUserTransition($newStatus, $user, $flow);
    }


    /**
     * Extension Point
     */
    protected function canUserTransition($newStatus, $user, array $flow): bool
    {
        return $user->hasAnyRole($flow[$newStatus->value] ?? []);
    }


    /**
     * Query 
     */
    public function getNextStatuses(): array
    {
        $enum = $this->getStatusEnumClass();
        $user = auth()->user();

        return collect($this->getStatusTransitions())
            ->keys()
            ->map(fn($value) => $enum::from($value))
            ->filter(fn($status) => $this->canChangeStatusTo($status, $user))
            ->values()
            ->toArray()
        ;
    }

    public function getAvailableStatusOptions(): array
    {
        return collect(array_merge([$this->getStatus()], $this->getNextStatuses()))
            ->unique(fn($status) => $status->value)
            ->mapWithKeys(fn($status) => [
                (string) $status->value => $status->label()
            ])
            ->toArray()
        ;
    }


    /**
     * Internal
     */
    protected function getStatus()
    {
        $field = $this->getStatusField();
        return $this->{$field};
    }

    protected function normalizeStatus($status)
    {
        if ($status === null) {
            return null;
        }

        $enum = $this->getStatusEnumClass();

        return $status instanceof $enum
            ? $status
            : $enum::tryFrom($status)
            ?? throw new \InvalidArgumentException("Invalid status value")
        ;
    }

    protected function getStatusTransitions(): array
    {
        return $this->getStatus()->transitions();
    }
}