<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\TextMeasurer;
use App\Exceptions\RenderException;
use App\Services\TextFitter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TextFitterTest extends TestCase
{
    /**
     * Lebar palsu: 1 karakter = 0,1 x ukuran huruf (mm), sehingga angkanya mudah dihitung.
     */
    private function fitter(): TextFitter
    {
        $measurer = new class implements TextMeasurer
        {
            public function width(string $text, float $size): float
            {
                return mb_strlen($text, 'UTF-8') * $size / 10;
            }
        };

        return new TextFitter($measurer);
    }

    public function test_returns_initial_size_when_text_already_fits(): void
    {
        $result = $this->fitter()->fit('Arief Soekardi', maxWidth: 148, size: 28, minSize: 16);

        $this->assertSame(['Arief Soekardi'], $result->lines);
        $this->assertSame(28.0, $result->size);
        $this->assertFalse($result->isWrapped());
    }

    public function test_shrinks_in_half_point_steps(): void
    {
        $result = $this->fitter()->fit(str_repeat('A', 40), maxWidth: 100, size: 28, minSize: 16);

        $this->assertSame(25.0, $result->size);
        $this->assertSame([str_repeat('A', 40)], $result->lines);
    }

    public function test_stops_shrinking_as_soon_as_lines_fit(): void
    {
        $result = $this->fitter()->fit('AAAA BBBB CCCC', maxWidth: 10, size: 20, minSize: 10, maxLines: 2);

        $this->assertSame(11.0, $result->size);
        $this->assertSame(['AAAA BBBB', 'CCCC'], $result->lines);
        $this->assertTrue($result->isWrapped());
    }

    public function test_breaks_at_the_nearest_space_to_balance_lines(): void
    {
        $result = $this->fitter()->fit('AAAA BBBB CCC', maxWidth: 9, size: 10, minSize: 10, maxLines: 2);

        $this->assertSame(['AAAA', 'BBBB CCC'], $result->lines);
    }

    public function test_greedy_keeps_first_line_full(): void
    {
        $result = $this->fitter()->fit(
            'AAAA BBBB CCC',
            maxWidth: 9,
            size: 10,
            minSize: 10,
            maxLines: 2,
            balance: false,
        );

        $this->assertSame(['AAAA BBBB', 'CCC'], $result->lines);
    }

    public function test_never_truncates_text(): void
    {
        $nama = "Mu'ammal Hamidy";

        $result = $this->fitter()->fit($nama, maxWidth: 8, size: 10, minSize: 10, maxLines: 2);

        $this->assertSame($nama, implode(' ', $result->lines));
        $this->assertStringNotContainsString('…', implode(' ', $result->lines));
        $this->assertStringNotContainsString('...', implode(' ', $result->lines));
    }

    public function test_throws_when_single_word_is_wider_than_maximum(): void
    {
        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('tidak muat');

        $this->fitter()->fit(str_repeat('A', 16), maxWidth: 5, size: 10, minSize: 10, maxLines: 2);
    }

    public function test_throws_when_text_needs_more_lines_than_allowed(): void
    {
        $this->expectException(RenderException::class);

        $this->fitter()->fit('AAAA BBBB CCCC', maxWidth: 10, size: 20, minSize: 10, maxLines: 1);
    }

    public function test_trims_and_collapses_whitespace(): void
    {
        $result = $this->fitter()->fit('  Arief   Soekardi  ', maxWidth: 148, size: 28, minSize: 16);

        $this->assertSame(['Arief Soekardi'], $result->lines);
    }

    public function test_empty_text_returns_single_empty_line(): void
    {
        $result = $this->fitter()->fit('   ', maxWidth: 148, size: 28, minSize: 16);

        $this->assertSame([''], $result->lines);
        $this->assertSame(28.0, $result->size);
    }

    public function test_rejects_invalid_max_width(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->fitter()->fit('Arief', maxWidth: 0, size: 28, minSize: 16);
    }

    public function test_rejects_min_size_larger_than_size(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->fitter()->fit('Arief', maxWidth: 148, size: 16, minSize: 28);
    }

    public function test_rejects_invalid_max_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->fitter()->fit('Arief', maxWidth: 148, size: 28, minSize: 16, maxLines: 0);
    }
}
