<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Sends SMTP mail entirely outside the web request.
 *
 * Strategy (tried in order):
 *
 * 1. Background process — serialise the Symfony message to a temp file,
 *    then spawn a detached `php artisan smtp:send {file}` process that
 *    runs after PHP returns the HTTP response.  On the PHP built-in
 *    server (`php artisan serve`) this is the only strategy that lets
 *    the browser see the redirect before Gmail's TLS handshake finishes.
 *
 * 2. Terminating callback — the original approach, used as a fallback if
 *    proc spawning fails (e.g. exec() disabled, PHP-FPM + fastcgi already
 *    finishes the response before terminate() runs in production).
 */
class DeferredTransport implements TransportInterface
{
    public function __construct(
        private readonly TransportInterface $inner
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if ($this->spawnBackground($message, $envelope)) {
            return null;
        }

        // ── Fallback: terminating callback (blocks built-in server until SMTP finishes) ──
        $inner          = $this->inner;
        $clonedMessage  = clone $message;
        $clonedEnvelope = $envelope ? clone $envelope : null;

        app()->terminating(static function () use ($inner, $clonedMessage, $clonedEnvelope): void {
            try {
                $inner->send($clonedMessage, $clonedEnvelope);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return null;
    }

    /**
     * Write the payload to a temp file and spawn a detached PHP process
     * (`php artisan smtp:send {file}`) that sends the email independently
     * of this web request.
     *
     * Returns true if a background process was successfully launched.
     */
    private function spawnBackground(RawMessage $message, ?Envelope $envelope): bool
    {
        try {
            $tmpDir  = sys_get_temp_dir();
            $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'smtp_' . bin2hex(random_bytes(8)) . '.ser';

            file_put_contents($tmpFile, serialize([
                'message'  => $message,
                'envelope' => $envelope,
            ]));

            $php     = PHP_BINARY;
            $artisan = base_path('artisan');

            if (PHP_OS_FAMILY === 'Windows') {
                // `start /B` creates a detached process that outlives cmd.exe.
                // popen() returns immediately once cmd.exe hands off to start.
                $cmd    = sprintf('start /B "" "%s" "%s" smtp:send "%s" 2>nul 1>nul', $php, $artisan, $tmpFile);
                $handle = @popen($cmd, 'r');
                if ($handle !== false) {
                    @pclose($handle);
                    return true;
                }
            } else {
                // The trailing & detaches on POSIX systems.
                $cmd = sprintf('"%s" "%s" smtp:send "%s" >/dev/null 2>&1 &', $php, $artisan, $tmpFile);
                @exec($cmd);
                return true;
            }
        } catch (\Throwable) {
            // Swallow — the caller will try the terminating-callback fallback.
        }

        return false;
    }

    public function __toString(): string
    {
        return 'deferred+' . $this->inner;
    }
}
