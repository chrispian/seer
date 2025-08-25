<?php

namespace Tests\Feature;

use App\Actions\RouteFragment;
use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FragmentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_input_creates_single_fragment()
    {
        $input = 'This is a test fragment message';
        
        $fragment1 = app(RouteFragment::class)($input);
        $fragment2 = app(RouteFragment::class)($input);
        
        $this->assertEquals($fragment1->id, $fragment2->id);
        $this->assertEquals(1, Fragment::count());
    }

    public function test_whitespace_differences_treated_as_same()
    {
        $input1 = '  This is   a test  ';
        $input2 = 'This is a test';
        
        $fragment1 = app(RouteFragment::class)($input1);
        $fragment2 = app(RouteFragment::class)($input2);
        
        $this->assertEquals($fragment1->id, $fragment2->id);
        $this->assertEquals(1, Fragment::count());
    }

    public function test_url_case_differences_treated_as_same()
    {
        $input1 = 'Check HTTPS://EXAMPLE.COM for info';
        $input2 = 'Check https://example.com for info';
        
        $fragment1 = app(RouteFragment::class)($input1);
        $fragment2 = app(RouteFragment::class)($input2);
        
        $this->assertEquals($fragment1->id, $fragment2->id);
        $this->assertEquals(1, Fragment::count());
    }

    public function test_different_content_creates_different_fragments()
    {
        $fragment1 = app(RouteFragment::class)('First message');
        $fragment2 = app(RouteFragment::class)('Second message');
        
        $this->assertNotEquals($fragment1->id, $fragment2->id);
        $this->assertEquals(2, Fragment::count());
    }

    public function test_fragments_store_normalized_hash_and_bucket()
    {
        $input = '  Test message  ';
        $fragment = app(RouteFragment::class)($input);
        
        $this->assertNotNull($fragment->input_hash);
        $this->assertNotNull($fragment->hash_bucket);
        $this->assertEquals(64, strlen($fragment->input_hash)); // SHA256 length
        $this->assertIsInt($fragment->hash_bucket);
    }

    public function test_original_message_preserved()
    {
        $input = '  Test message with  extra   spaces  ';
        $fragment = app(RouteFragment::class)($input);
        
        $this->assertEquals($input, $fragment->message);
    }

    public function test_time_bucket_prevents_duplicates_within_window()
    {
        // Mock a consistent time bucket
        $mockBucket = floor(time() / 600);
        
        $input = 'Test message for time bucket';
        $fragment1 = app(RouteFragment::class)($input);
        $fragment2 = app(RouteFragment::class)($input);
        
        $this->assertEquals($mockBucket, $fragment1->hash_bucket);
        $this->assertEquals($fragment1->id, $fragment2->id);
        $this->assertEquals(1, Fragment::count());
    }

    public function test_complex_normalization_creates_same_hash()
    {
        $input1 = "  Check https://EXAMPLE.COM/api  \r\n\n  #tag content  ";
        $input2 = "Check https://example.com/api\n#tag content";
        
        $fragment1 = app(RouteFragment::class)($input1);
        $fragment2 = app(RouteFragment::class)($input2);
        
        $this->assertEquals($fragment1->input_hash, $fragment2->input_hash);
        $this->assertEquals($fragment1->id, $fragment2->id);
        $this->assertEquals(1, Fragment::count());
    }
}
