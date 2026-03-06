<?php

namespace NeosRulez\DirectMail\Domain\Model\Mailer;

interface MailerInterface
{
    public function setFrom(string $mail, string $name = ''): MailerInterface;

    public function setTo(string $mail, string $name = ''): MailerInterface;

    public function setReplyTo(string $mail, string $name = ''): MailerInterface;

    public function setSubject(string $subject): MailerInterface;

    public function setHtmlBody(string $body): MailerInterface;

    public function addAttachment(string $content, ?string $filename = null, ?string $contentType = null, ?string $encoding = null): MailerInterface;

    public function send(): bool;
}
