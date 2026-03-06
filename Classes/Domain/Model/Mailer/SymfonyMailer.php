<?php

namespace NeosRulez\DirectMail\Domain\Model\Mailer;

use Neos\Flow\Annotations as Flow;
use Neos\SymfonyMailer\Service\MailerService;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class SymfonyMailer implements MailerInterface
{
    protected Email $mail;

    /**
     * @var MailerService
     * @Flow\Inject
     */
    protected $mailerService;

    public function __construct()
    {
        $this->mail = new Email();
    }

    public function setFrom(string $mail, string $name = ''): MailerInterface
    {
        $this->mail->from(new Address($mail, $name));
        return $this;
    }

    public function setTo(string $mail, string $name = ''): MailerInterface
    {
        $this->mail->to(new Address($mail, $name));
        return $this;
    }

    public function setReplyTo(string $mail, string $name = ''): MailerInterface
    {
        $this->mail->replyTo(new Address($mail, $name));
        return $this;
    }

    public function setSubject(string $subject): MailerInterface
    {
        $this->mail->subject($subject);
        return $this;
    }

    public function setHtmlBody(string $body): MailerInterface
    {
        $this->mail->html($body);
        return $this;
    }

    public function addAttachment(string $content, ?string $filename = null, ?string $contentType = null, ?string $encoding = null): MailerInterface
    {
        // Implementation for adding attachment
        $this->mail->addPart(new DataPart(
            $content,
            $filename,
            $contentType,
            $encoding
        ));
        return $this;
    }

    public function send(): bool
    {
        try {
            $mailerService = $this->mailerService->getMailer();
            $mailerService->send($this->mail);
            return true;
        } catch (\Exception $e) {
            // TODO: Log the exception or handle it as needed
            return false;
        }
    }
}
