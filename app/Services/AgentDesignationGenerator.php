<?php

namespace App\Services;

use App\Models\Agent;

class AgentDesignationGenerator
{
    private const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generate(): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $designation = $this->generateDesignation();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Failed to generate unique designation after '.$maxAttempts.' attempts');
            }
        } while (Agent::where('designation', $designation)->exists());

        return $designation;
    }

    private function generateDesignation(): string
    {
        $dashPosition = random_int(1, 3);

        $parts = [];
        for ($i = 0; $i < 4; $i++) {
            if ($i === $dashPosition) {
                $parts[] = '-';
            }
            $parts[] = $this->randomChar();
        }

        $designation = implode('', $parts);

        if (strlen($designation) !== 5) {
            $designation = substr($designation, 0, 5);
        }

        return $designation;
    }

    private function randomChar(): string
    {
        return self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
    }
}
