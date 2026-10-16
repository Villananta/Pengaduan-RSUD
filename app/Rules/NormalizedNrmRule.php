<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NormalizedNrmRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = strtoupper(preg_replace('/[^0-9A-Z]/', '', (string) $value));

        if (strlen($normalized) < 4) {
            $fail('Nomor rekam media minimal 4 karakter setelah dinormalisasi (tanpa spasi, tanda hubung, atau titik).');
        }

        if (! preg_match('/^[0-9A-Z]+$/', $normalized)) {
            $fail('Nomor rekam media hanya boleh berisi angka dan huruf (spasi, tanda hubung, titik akan diabaikan).');
        }
    }
}