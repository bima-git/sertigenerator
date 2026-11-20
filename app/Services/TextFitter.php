<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TextMeasurer;
use App\DTO\FitResult;
use App\Exceptions\RenderException;
use InvalidArgumentException;

/**
 * Pengepasan teks sertifikat sesuai Design System bagian 9.5:
 * ukur, turunkan 0,5 pt, pecah baris, lalu tolak bila tetap tidak muat.
 */
final class TextFitter
{
    private const STEP = 0.5;

    private const BISECTION_STEPS = 24;

    public function __construct(
        private readonly TextMeasurer $measurer,
    ) {}

    /**
     * @param  float  $maxWidth  Lebar maksimum dalam mm.
     * @param  float  $size  Ukuran awal dalam pt.
     * @param  float  $minSize  Ukuran minimum dalam pt.
     * @param  int  $maxLines  Jumlah baris maksimum yang diizinkan.
     * @param  bool  $balance  Seimbangkan panjang baris; false = isi baris sedapat mungkin (greedy).
     */
    public function fit(
        string $text,
        float $maxWidth,
        float $size,
        float $minSize,
        int $maxLines = 1,
        bool $balance = true,
    ): FitResult {
        if ($maxWidth <= 0.0) {
            throw new InvalidArgumentException('Lebar maksimum harus lebih besar dari 0 mm.');
        }

        if ($minSize <= 0.0) {
            throw new InvalidArgumentException('Ukuran minimum harus lebih besar dari 0 pt.');
        }

        if ($minSize > $size) {
            throw new InvalidArgumentException('Ukuran minimum tidak boleh melebihi ukuran awal.');
        }

        if ($maxLines < 1) {
            throw new InvalidArgumentException('Jumlah baris maksimum minimal 1.');
        }

        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '') {
            return new FitResult([''], $size);
        }

        // Teks dianggap muat bila masuk satu baris atau bisa dipecah dalam batas
        // jumlah baris; ukuran baru diturunkan bila keduanya gagal.
        $current = $size;
        $lines = $this->muat($text, $current, $maxWidth, $maxLines, $balance);

        while ($lines === null && $current > $minSize) {
            $current = max($minSize, round($current - self::STEP, 2));
            $lines = $this->muat($text, $current, $maxWidth, $maxLines, $balance);
        }

        if ($lines !== null) {
            return new FitResult($lines, $current);
        }

        throw new RenderException(sprintf(
            'Teks "%s" tidak muat pada lebar %.1f mm walau sudah %.1f pt dan maksimum %d baris.',
            $text,
            $maxWidth,
            $current,
            $maxLines,
        ));
    }

    /**
     * @return list<string>|null Baris pada ukuran ini, atau null bila tidak muat.
     */
    private function muat(string $text, float $size, float $maxWidth, int $maxLines, bool $balance): ?array
    {
        if ($this->measurer->width($text, $size) <= $maxWidth) {
            return [$text];
        }

        return $this->splitIntoLines($text, $size, $maxWidth, $maxLines, $balance);
    }

    /**
     * Memecah teks pada spasi terdekat tanpa memotong isi teks.
     *
     * @return list<string>|null Null bila teks tetap tidak muat.
     */
    private function splitIntoLines(
        string $text,
        float $size,
        float $maxWidth,
        int $maxLines,
        bool $balance,
    ): ?array {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if ($this->measurer->width($word, $size) > $maxWidth) {
                return null;
            }
        }

        $widest = $this->greedy($words, $size, $maxWidth);

        if ($widest === null || count($widest) > $maxLines) {
            return null;
        }

        if (count($widest) === 1 || ! $balance) {
            return $widest;
        }

        // Cari lebar terkecil yang masih menghasilkan jumlah baris yang diizinkan
        // supaya pemecahan jatuh pada spasi terdekat dan baris tidak berat sebelah.
        $low = 0.0;
        $high = $maxWidth;

        for ($i = 0; $i < self::BISECTION_STEPS; $i++) {
            $mid = ($low + $high) / 2;
            $candidate = $this->greedy($words, $size, $mid);

            if ($candidate !== null && count($candidate) <= $maxLines) {
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        return $this->greedy($words, $size, $high) ?? $widest;
    }

    /**
     * @param  list<string>  $words
     * @return list<string>|null
     */
    private function greedy(array $words, float $size, float $maxWidth): ?array
    {
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($this->measurer->width($candidate, $size) <= $maxWidth) {
                $line = $candidate;

                continue;
            }

            if ($line === '' || $this->measurer->width($word, $size) > $maxWidth) {
                return null;
            }

            $lines[] = $line;
            $line = $word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }
}
