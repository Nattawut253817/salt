<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;

/**
 * Internal command — DO NOT call manually.
 *
 * Spawned by DeferredTransport to send an SMTP message in a background
 * PHP process so that the web request can return its HTTP response
 * before the Gmail TLS handshake begins.
 *
 * Usage: php artisan smtp:send {/tmp/smtp_<hex>.ser}
 */
class SmtpSend extends Command
{
    protected $signature   = 'smtp:send {file : Absolute path to the serialised mail payload}';
    protected $description = 'Send a deferred SMTP message from a temp payload file (internal use only)';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            // Already sent by a previous attempt or stale — silently succeed.
            return self::SUCCESS;
        }

        try {
            $payload = @unserialize((string) file_get_contents($file));
            @unlink($file); // Remove immediately so a crash does not leave it forever.

            if (! $payload || ! isset($payload['message'])) {
                $this->error('smtp:send — invalid payload.');
                return self::FAILURE;
            }

            /** @var RawMessage $message */
            $message  = $payload['message'];
            /** @var Envelope|null $envelope */
            $envelope = $payload['envelope'] ?? null;

            // Build a plain MailManager (NOT DeferringMailManager) to get
            // the raw Symfony SMTP transport without re-wrapping it in
            // DeferredTransport and triggering another background spawn.
            $transport = (new MailManager($this->getLaravel()))
                ->mailer()
                ->getSymfonyTransport();

            $transport->send($message, $envelope);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            @unlink($file);
            $this->error('smtp:send failed: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }
    }
}
