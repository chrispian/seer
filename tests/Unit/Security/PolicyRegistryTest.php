<?php

namespace Tests\Unit\Security;

use App\Services\Security\PolicyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyRegistryTest extends TestCase
{
    use RefreshDatabase;

    private PolicyRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'SecurityPolicySeeder']);

        $this->registry = app(PolicyRegistry::class);
    }

    public function test_tool_allowlist_matching(): void
    {
        $result = $this->registry->isToolAllowed('shell');
        $this->assertTrue($result['allowed']);
        $this->assertEquals('shell', $result['matched_rule']);

        $result = $this->registry->isToolAllowed('fs.read');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isToolAllowed('admin.delete');
        $this->assertFalse($result['allowed']);
    }

    public function test_wildcard_tool_matching(): void
    {
        $result = $this->registry->isToolAllowed('fs.read');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isToolAllowed('fs.write');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isToolAllowed('mcp.server.start');
        $this->assertTrue($result['allowed']);
    }

    public function test_command_allowlist(): void
    {
        $result = $this->registry->isCommandAllowed('ls -la');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isCommandAllowed('git status');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isCommandAllowed('rm -rf /');
        $this->assertFalse($result['allowed']);

        $result = $this->registry->isCommandAllowed('sudo apt-get install');
        $this->assertFalse($result['allowed']);
    }

    public function test_domain_allowlist(): void
    {
        $result = $this->registry->isDomainAllowed('api.github.com');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isDomainAllowed('cdn.github.com');
        $this->assertTrue($result['allowed']);

        $result = $this->registry->isDomainAllowed('localhost');
        $this->assertFalse($result['allowed']);
    }

    public function test_default_deny_when_no_match(): void
    {
        $result = $this->registry->isToolAllowed('unknown.tool');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('default deny', strtolower($result['reason']));
    }

    public function test_get_stats(): void
    {
        $stats = $this->registry->getStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_action', $stats);
        $this->assertEquals(33, $stats['total']);
    }

    public function test_risk_weight_retrieval(): void
    {
        $weight = $this->registry->getRiskWeight('command', 'git');
        $this->assertEquals(5, $weight);

        $weight = $this->registry->getRiskWeight('command', 'npm');
        $this->assertEquals(10, $weight);
    }
}
