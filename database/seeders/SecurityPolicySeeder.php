<?php

namespace Database\Seeders;

use App\Models\SecurityPolicy;
use Illuminate\Database\Seeder;

class SecurityPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            // === TOOL POLICIES ===
            ['policy_type' => 'tool', 'category' => null, 'pattern' => 'shell', 'action' => 'allow', 'priority' => 100, 'description' => 'Allow shell tool'],
            ['policy_type' => 'tool', 'category' => null, 'pattern' => 'fs.*', 'action' => 'allow', 'priority' => 100, 'description' => 'Allow filesystem tools'],
            ['policy_type' => 'tool', 'category' => null, 'pattern' => 'mcp.*', 'action' => 'allow', 'priority' => 100, 'description' => 'Allow MCP tools'],
            ['policy_type' => 'tool', 'category' => null, 'pattern' => 'admin.*', 'action' => 'deny', 'priority' => 50, 'description' => 'Deny admin tools'],

            // === SHELL COMMAND POLICIES ===
            // Allowed commands (priority 100 = normal)
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'ls', 'action' => 'allow', 'priority' => 100, 'description' => 'List directory'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'pwd', 'action' => 'allow', 'priority' => 100, 'description' => 'Print working directory'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'echo', 'action' => 'allow', 'priority' => 100, 'description' => 'Echo text'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'cat', 'action' => 'allow', 'priority' => 100, 'description' => 'Concatenate files'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'grep', 'action' => 'allow', 'priority' => 100, 'description' => 'Search text'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'find', 'action' => 'allow', 'priority' => 100, 'description' => 'Find files'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'git', 'action' => 'allow', 'priority' => 100, 'description' => 'Git commands', 'metadata' => ['risk_weight' => 5]],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'npm', 'action' => 'allow', 'priority' => 100, 'description' => 'NPM commands', 'metadata' => ['risk_weight' => 10]],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'composer', 'action' => 'allow', 'priority' => 100, 'description' => 'Composer commands', 'metadata' => ['risk_weight' => 10]],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'php', 'action' => 'allow', 'priority' => 100, 'description' => 'PHP commands', 'metadata' => ['risk_weight' => 15]],

            // Denied commands (priority 50 = higher priority than allows)
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'rm', 'action' => 'deny', 'priority' => 50, 'description' => 'Remove files - dangerous'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'dd', 'action' => 'deny', 'priority' => 50, 'description' => 'Disk destroyer - very dangerous'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'mkfs', 'action' => 'deny', 'priority' => 50, 'description' => 'Make filesystem - dangerous'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'sudo', 'action' => 'deny', 'priority' => 50, 'description' => 'Privileged access - not allowed'],
            ['policy_type' => 'command', 'category' => 'shell', 'pattern' => 'su', 'action' => 'deny', 'priority' => 50, 'description' => 'Switch user - not allowed'],

            // === FILESYSTEM POLICIES ===
            // Allowed paths
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => '/workspace/*', 'action' => 'allow', 'priority' => 100, 'description' => 'Workspace directory'],
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => '/tmp/*', 'action' => 'allow', 'priority' => 100, 'description' => 'Temp directory'],
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => base_path().'/*', 'action' => 'allow', 'priority' => 100, 'description' => 'Application directory'],

            // Denied paths (higher priority)
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => '/etc/*', 'action' => 'deny', 'priority' => 50, 'description' => 'System configuration - forbidden'],
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => '/var/*', 'action' => 'deny', 'priority' => 50, 'description' => 'System files - forbidden'],
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => '~/.ssh/*', 'action' => 'deny', 'priority' => 50, 'description' => 'SSH keys - forbidden'],
            ['policy_type' => 'path', 'category' => 'filesystem', 'pattern' => '/System/*', 'action' => 'deny', 'priority' => 50, 'description' => 'macOS system - forbidden'],

            // === NETWORK POLICIES ===
            // Allowed domains
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => '*.github.com', 'action' => 'allow', 'priority' => 100, 'description' => 'GitHub domains'],
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => '*.githubusercontent.com', 'action' => 'allow', 'priority' => 100, 'description' => 'GitHub content'],
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => 'api.openai.com', 'action' => 'allow', 'priority' => 100, 'description' => 'OpenAI API'],
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => 'api.anthropic.com', 'action' => 'allow', 'priority' => 100, 'description' => 'Anthropic API'],

            // Denied domains (SSRF prevention)
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => 'localhost', 'action' => 'deny', 'priority' => 50, 'description' => 'Localhost - SSRF prevention'],
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => '*.local', 'action' => 'deny', 'priority' => 50, 'description' => 'Local domains - SSRF prevention'],
            ['policy_type' => 'domain', 'category' => 'network', 'pattern' => '*.internal', 'action' => 'deny', 'priority' => 50, 'description' => 'Internal domains - SSRF prevention'],
        ];

        foreach ($policies as $policy) {
            SecurityPolicy::updateOrCreate(
                [
                    'policy_type' => $policy['policy_type'],
                    'category' => $policy['category'],
                    'pattern' => $policy['pattern'],
                    'action' => $policy['action'],
                ],
                $policy
            );
        }

        $this->command->info('Seeded '.count($policies).' security policies');
    }
}
