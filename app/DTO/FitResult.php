<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class FitResult
{
    /**
     * @param  list<string>  $lines
     */
    public function __construct(
        public array $lines,
        public float $size,
    ) {}

    public function lineCount(): int
    {
        return count($this->lines);
    }

    public function isWrapped(): bool
    {
        return $this->lineCount() > 1;
    }
}
