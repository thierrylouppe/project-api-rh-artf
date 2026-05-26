<?php

namespace App\Traits;

trait HasAutoSigle
{
    protected static function bootHasAutoSigle(): void
    {
        static::creating(function ($model) {
            if (empty($model->sigle) && ! empty($model->nom)) {
                $model->sigle = collect(explode(' ', $model->nom))
                    ->filter()
                    ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
                    ->implode('');
            }
        });
    }
}
