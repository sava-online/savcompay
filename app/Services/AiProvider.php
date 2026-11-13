<?php
namespace App\Services;

/**
 * AI provider abstraction. The browser never talks to a provider directly;
 * all requests go through GlassTube's PHP API which holds the secret key.
 */
interface AiProvider
{
    public function name(): string;

    /** Returns the provider's raw text response for a chat completion. */
    public function generate(string $system, string $user, string $task = ''): string;
}
