<?php

declare(strict_types=1);

namespace App\Contracts;

interface TextMeasurer
{
    /**
     * Lebar teks dalam milimeter pada ukuran huruf tertentu.
     */
    public function width(string $text, float $size): float;
}
