<?php

namespace Tests\Unit;

use App\Support\Mobile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MobileTest extends TestCase
{
    #[DataProvider('formats')]
    public function test_it_normalizes_common_formats(string $input): void
    {
        $this->assertSame('09123456789', Mobile::normalize($input));
    }

    /** @return array<string, array{string}> */
    public static function formats(): array
    {
        return [
            'canonical' => ['09123456789'],
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹'],
            'arabic digits' => ['٠٩١٢٣٤٥٦٧٨٩'],
            'plus 98' => ['+989123456789'],
            '0098' => ['00989123456789'],
            '98 prefix' => ['989123456789'],
            'no leading zero' => ['9123456789'],
            'spaces' => ['0912 345 6789'],
            'dashes' => ['0912-345-6789'],
        ];
    }

    public function test_it_validates(): void
    {
        $this->assertTrue(Mobile::isValid('09123456789'));
        $this->assertFalse(Mobile::isValid('0812345678'));
        $this->assertFalse(Mobile::isValid('091234567890'));
        $this->assertFalse(Mobile::isValid('abc'));
    }

    public function test_it_masks(): void
    {
        $this->assertSame('0912***6789', Mobile::mask('09123456789'));
    }

    public function test_latin_digits_strips_whitespace(): void
    {
        $this->assertSame('123456', Mobile::latinDigits(' ۱۲۳ ۴۵۶ '));
    }
}
