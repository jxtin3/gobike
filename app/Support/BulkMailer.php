<?php

namespace App\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

/**
 * Sends one email to each recipient in a list, so a single bad address never stops the rest.
 *
 * The mail server refusing one address (UnexpectedResponseException) skips only that person.
 * The mail server itself being unreachable (any other TransportException) stops the batch,
 * because every send after it would fail the same way.
 */
class BulkMailer
{
    /**
     * @param  iterable<object>  $recipients
     * @param  callable(object): array{0: string, 1: ?string, 2: Mailable}  $mailFor  [email, name, mailable]
     * @param  callable(object): void  $onSent  called after each successful send
     * @return array{sent: int, failed: int, stopped: bool}
     */
    public static function send(iterable $recipients, callable $mailFor, callable $onSent, string $what = 'email'): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            [$email, $name, $mailable] = $mailFor($recipient);

            try {
                Mail::to($email, $name)->send($mailable);
                $onSent($recipient);
                $sent++;
            } catch (UnexpectedResponseException $e) {
                // The server refused this one address; carry on with the others.
                $failed++;
                Log::warning("Could not send {$what}: the address was refused", ['email' => $email, 'error' => $e->getMessage()]);
            } catch (TransportException $e) {
                Log::error("{$what} stopped: the mail server could not be reached", ['error' => $e->getMessage(), 'sent' => $sent]);

                return ['sent' => $sent, 'failed' => $failed, 'stopped' => true];
            } catch (Throwable $e) {
                $failed++;
                Log::warning("Could not send {$what}", ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'stopped' => false];
    }
}
