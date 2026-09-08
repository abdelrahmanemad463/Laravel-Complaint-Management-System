<?php

namespace App\Models\Concerns;

trait HasLocalizedName
{
    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'ar') {
            return (string) ($this->name_ar ?: ($this->name_en ?: $this->name));
        }

        return (string) ($this->name_en ?: ($this->name_ar ?: $this->name));
    }
}