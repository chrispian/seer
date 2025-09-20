<?php

namespace Tests\Unit;

use App\Actions\NormalizeInput;
use Tests\TestCase;

class NormalizeInputTest extends TestCase
{
    private NormalizeInput $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new NormalizeInput;
    }

    public function test_normalizes_whitespace()
    {
        $result = ($this->normalizer)("  hello    world  \n\n  ");

        $this->assertEquals('hello world', $result['normalized']);
    }

    public function test_removes_zero_width_characters()
    {
        $text = "hello\u{200B}world\u{FEFF}test";
        $result = ($this->normalizer)($text);

        $this->assertEquals('hello world test', $result['normalized']);
    }

    public function test_normalizes_urls_to_lowercase()
    {
        $result = ($this->normalizer)('Check HTTPS://EXAMPLE.COM/PATH and keep Other CASE');

        $this->assertEquals('Check https://example.com/PATH and keep Other CASE', $result['normalized']);
    }

    public function test_normalizes_line_endings()
    {
        $result = ($this->normalizer)("line1\r\nline2\rline3\nline4");

        $this->assertEquals('line1 line2 line3 line4', $result['normalized']);
    }

    public function test_generates_consistent_hash()
    {
        $result1 = ($this->normalizer)('  hello    world  ');
        $result2 = ($this->normalizer)('hello world');

        $this->assertEquals($result1['hash'], $result2['hash']);
        $this->assertEquals(64, strlen($result1['hash'])); // SHA256 is 64 chars
    }

    public function test_generates_time_bucket()
    {
        $result = ($this->normalizer)('test input');

        $this->assertIsInt($result['bucket']);
        $this->assertEquals(floor(time() / 600), $result['bucket']);
    }

    public function test_preserves_original_input()
    {
        $original = '  MIXED case   and  SPACING  ';
        $result = ($this->normalizer)($original);

        $this->assertEquals($original, $result['original']);
        $this->assertEquals('MIXED case and SPACING', $result['normalized']);
    }

    public function test_handles_complex_text()
    {
        $complex = "  Check https://EXAMPLE.COM/api?param=VALUE  \r\n\n  #tag @person [link]\u{200B} extra   ";
        $result = ($this->normalizer)($complex);

        $expected = 'Check https://example.com/api?param=VALUE #tag @person [link] extra';
        $this->assertEquals($expected, $result['normalized']);
    }

    public function test_returns_all_required_fields()
    {
        $result = ($this->normalizer)('test');

        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('normalized', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('bucket', $result);
    }
}
