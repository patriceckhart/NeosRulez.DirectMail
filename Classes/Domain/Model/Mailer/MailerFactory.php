<?php

namespace NeosRulez\DirectMail\Domain\Model\Mailer;

class MailerFactory
{
    public static function createMailer(): MailerInterface
    {
        if (class_exists('\Neos\SymfonyMailer\Service\MailerService')) {
            return new SymfonyMailer();
        } else if (class_exists('\Neos\SwiftMailer\Message')) {
            return new SwiftMailer();
        }

        // No mailer implementation available, so we throw an exception
        throw new \RuntimeException('No mailer implementation available', 1772830048080);
    }
}
