<?php

namespace App\Models\Concerns;

/**
 * 98XXXXXX12 — what a role without `leads.view_contact` sees.
 *
 * Shared rather than repeated: three models render contact numbers into admin
 * screens and exports, and a model that quietly lacks the accessor renders an
 * empty cell instead of a masked number.
 */
trait MasksMobile
{
    public function getMaskedMobileAttribute(): string
    {
        $mobile = (string) $this->mobile;

        return strlen($mobile) < 10
            ? $mobile
            : substr($mobile, 0, 2).str_repeat('X', strlen($mobile) - 4).substr($mobile, -2);
    }
}
