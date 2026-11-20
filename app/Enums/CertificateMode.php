<?php

declare(strict_types=1);

namespace App\Enums;

use InvalidArgumentException;

enum CertificateMode: string
{
    case Terbit = 'terbit';
    case Pratinjau = 'pratinjau';
    case Dibatalkan = 'dibatalkan';

    public static function fromInput(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new InvalidArgumentException("Mode sertifikat tidak dikenal: {$value}.");
    }

    public function label(): string
    {
        return match ($this) {
            self::Terbit => 'Terbit',
            self::Pratinjau => 'Pratinjau',
            self::Dibatalkan => 'Dibatalkan',
        };
    }
}
