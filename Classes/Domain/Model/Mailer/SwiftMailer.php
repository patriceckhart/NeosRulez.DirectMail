<?php

namespace NeosRulez\DirectMail\Domain\Model\Mailer;

use Neos\SwiftMailer\Message;

class SwiftMailer implements MailerInterface
{
    protected Message $message;

    public function __construct()
    {
        $this->message = new Message();
    }

    public function setFrom(string $mail, string $name = ''): MailerInterface
    {
        $this->message->setFrom([$mail => $name ?: $mail]);
        return $this;
    }

    public function setTo(string $mail, string $name = ''): MailerInterface
    {
        $this->message->setTo([$mail => $name ?: $mail]);
        return $this;
    }

    public function setReplyTo(string $mail, string $name = ''): MailerInterface
    {
        $this->message->setReplyTo([$mail => $name ?: $mail]);
        return $this;
    }

    public function setSubject(string $subject): MailerInterface
    {
        $this->message->setSubject($subject);
        return $this;
    }

    public function setHtmlBody(string $body): MailerInterface
    {
        $this->message->setBody($body, 'text/html');
        return $this;
    }

    public function addAttachment(string $content, ?string $filename = null, ?string $contentType = null, ?string $encoding = null): MailerInterface
    {
        $this->message->attach(new \Swift_Attachment($content, $filename, $contentType));
        return $this;
    }

    public function send(): bool
    {
        return $this->message->send() > 0;
    }
}
