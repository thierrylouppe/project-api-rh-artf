<?php

namespace App\Traits;

trait HasFilterScope
{
    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '' && in_array($key, $this->filterable ?? [], true)) {
                $query->where($key, $value);
            }
        }

        return $query;
    }
}
