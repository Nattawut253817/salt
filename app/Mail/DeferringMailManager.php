<?php

namespace App\Mail;

use App\Mail\Transport\DeferredTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Mailer;

/**
 * Extends Laravel's MailManager to wrap every resolved Mailer's Symfony
 * transport with DeferredTransport, making ALL mail sends non-blocking.
 *
 * Registered in AppServiceProvider::register() over the 'mail.manager'
 * singleton so it transparently replaces the default MailManager.
 */
class DeferringMailManager extends MailManager
{
    /**
     * Resolve a named mailer and wrap its transport so sends happen
     * after the HTTP response, not during it.
     */
    protected function resolve($name): Mailer
    {
        $mailer = parent::resolve($name);

        $mailer->setSymfonyTransport(
            new DeferredTransport($mailer->getSymfonyTransport())
        );

        return $mailer;
    }
}
