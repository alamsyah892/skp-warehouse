<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class BelongsToCompany implements ValidationRule
{
    public function __construct(
        private ?int $companyId,
        private string $modelClass,
        private string $mode = 'direct',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || blank($this->companyId)) {
            return;
        }

        /** @var Model $modelClass */
        $modelClass = $this->modelClass;

        $exists = match ($this->mode) {
            'direct' => $modelClass::query()
                ->whereKey($value)
                ->where('company_id', $this->companyId)
                ->exists(),
            'many' => $modelClass::query()
                ->whereKey($value)
                ->whereHas('companies', fn ($query) => $query->where('companies.id', $this->companyId))
                ->exists(),
            default => false,
        };

        if (! $exists) {
            $fail('Data yang dipilih tidak sesuai dengan perusahaan yang dipilih.');
        }
    }
}
