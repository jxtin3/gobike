<?php

namespace App\Services\Push;

/** Sends one push message to one phone. Swapped for a fake in tests. */
interface FcmSender
{
    /**
     * @param  array<string, string>  $data  extra values the app reads (all strings)
     * @return bool false when the token is no longer valid (the app was uninstalled), so it can be deleted
     */
    public function send(string $token, string $title, string $body, array $data, string $channel): bool;

    /** True when the server has Firebase credentials and can send. */
    public function enabled(): bool;
}
