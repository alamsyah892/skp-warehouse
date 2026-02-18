<?php

namespace App\Models\Concerns;

trait DefaultEmptyString
{
    protected static function bootDefaultEmptyString(): void
    {
        static::saving(function ($model) {
            if (property_exists($model, 'defaultEmptyStringFields')) {
                foreach ($model->defaultEmptyStringFields as $field) {
                    $model->{$field} = $model->{$field} ?? '';
                }
            }
        });
    }
}
