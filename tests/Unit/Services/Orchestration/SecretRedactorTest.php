<?php

use App\Services\Orchestration\Security\SecretRedactor;

test('redacts AWS access key', function () {
    $redactor = new SecretRedactor;
    $content = 'AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE';

    $redacted = $redactor->redact($content);

    expect($redacted)->not->toContain('AKIAIOSFODNN7EXAMPLE')
        ->and($redacted)->toContain('[REDACTED:AWS_ACCESS_KEY]');
});

test('redacts AWS secret key', function () {
    $redactor = new SecretRedactor;
    $content = 'AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY';

    $redacted = $redactor->redact($content);

    expect($redacted)->not->toContain('wJalrXUtnFEMI')
        ->and($redacted)->toContain('[REDACTED:AWS_SECRET_KEY]');
});

test('redacts APP_KEY', function () {
    $redactor = new SecretRedactor;
    $content = 'APP_KEY=base64:abc123def456';

    $redacted = $redactor->redact($content);

    expect($redacted)->not->toContain('abc123def456')
        ->and($redacted)->toContain('[REDACTED:APP_KEY]');
});

test('redacts Bearer tokens', function () {
    $redactor = new SecretRedactor;
    $content = 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';

    $redacted = $redactor->redact($content);

    expect($redacted)->not->toContain('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9')
        ->and($redacted)->toContain('[REDACTED:BEARER_TOKEN]');
});

test('redacts OpenAI API key', function () {
    $redactor = new SecretRedactor;
    $content = 'OPENAI_API_KEY=sk-1234567890abcdef';

    $redacted = $redactor->redact($content);

    expect($redacted)->not->toContain('sk-1234567890abcdef')
        ->and($redacted)->toContain('[REDACTED:OPENAI_API_KEY]');
});

test('redacts multiple secrets', function () {
    $redactor = new SecretRedactor;
    $content = 'AWS_ACCESS_KEY_ID=AKIA123
APP_KEY=base64:secret
Bearer tokendata';

    $redacted = $redactor->redact($content);

    expect($redacted)->not->toContain('AKIA123')
        ->and($redacted)->not->toContain('secret')
        ->and($redacted)->not->toContain('tokendata')
        ->and($redacted)->toContain('[REDACTED:');
});

test('hasSecrets detects secrets', function () {
    $redactor = new SecretRedactor;

    expect($redactor->hasSecrets('APP_KEY=secret123'))->toBeTrue()
        ->and($redactor->hasSecrets('No secrets here'))->toBeFalse();
});

test('scan returns findings', function () {
    $redactor = new SecretRedactor;
    $content = 'AWS_ACCESS_KEY_ID=AKIA123 and APP_KEY=base64:test';

    $findings = $redactor->scan($content);

    expect($findings)->toHaveCount(2)
        ->and($findings[0])->toHaveKey('type')
        ->and($findings[0])->toHaveKey('pattern');
});
