<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @Flow\Scope("singleton")
 */
class MailService {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\CssToInlineService
     */
    protected $cssToInlineService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $i18nService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="contentDimensions")
     * @var array
     */
    protected $contentDimensions;

    /**
     * @param string $nodeUri
     * @param array $recipient
     * @param string $subject
     * @return boolean
     */
    public function execute(string $nodeUri, array $recipient, string $subject):bool
    {
        $email = $recipient['email'];
        $email = str_replace(' ', '', $email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail = new \Neos\SwiftMailer\Message();
            $mail
                ->setFrom([$this->settings['senderMail'] => $this->settings['senderName']])
                ->setTo([$email => $recipient['firstname'] . ' ' . $recipient['lastname']])
                ->setSubject($subject);

            $renderUri = $this->settings['baseUri'] . '/directmail/' . base64_encode($this->addLanguageToNodeUri($nodeUri, $recipient));
            $file = file_get_contents($renderUri);

            $body = $this->replacePlaceholders($file, $recipient, $nodeUri);

            $mail->setBody($body, 'text/html');
            $mail->send();
        }
        return true;
    }

    /**
     * @param string $nodeUri
     * @param array $recipient
     * @return string
     */
    private function addLanguageToNodeUri(string $nodeUri, array $recipient):string
    {
        $uriSegment = false;
        if($recipient['language'] !== null) {
            if (array_key_exists('language', $this->contentDimensions)) {
                if (array_key_exists('presets', $this->contentDimensions['language'])) {
                    $presets = $this->contentDimensions['language']['presets'];
                    if (array_key_exists($recipient['language'], $presets)) {
                        $recipientPreset = $presets[$recipient['language']];
                        $uriSegment = $recipientPreset['uriSegment'];
                    }
                }
            }
        }
        $parsedUri = parse_url($nodeUri);
        return $parsedUri['scheme'] . '://' . $parsedUri['host'] . ($uriSegment ? '/' . $uriSegment : '') . $parsedUri['path'];
    }

    /**
     * @param array $recipient
     * @return string
     */
    public function sendDoubleOptIn(array $recipient):string
    {
        $mail = new \Neos\SwiftMailer\Message();
        $mail
            ->setFrom([$this->settings['senderMail'] => $this->settings['senderName']])
            ->setTo([$recipient['email'] => $recipient['firstname'] . ' ' . $recipient['lastname']])
            ->setSubject($this->translator->translateById('subject', [], null, null, $sourceName = 'Mail/DoubleOptIn', $packageKey = 'NeosRulez.DirectMail'));

        $setActiveUri = $this->settings['baseUri'] . '/setactive/'. $recipient['identifier'];
        $body = '
            <p>
                {salutation} {firstname} {lastname}!
            </p>
            <p>
            ' . $this->translator->translateById('body', [], null, null, $sourceName = 'Mail/DoubleOptIn', $packageKey = 'NeosRulez.DirectMail') . '
            </p>
            <p>
            <a href="' . $setActiveUri . '">' . $this->translator->translateById('btn', [], null, null, $sourceName = 'Mail/DoubleOptIn', $packageKey = 'NeosRulez.DirectMail') . '</a>
            </p>
        ';
        $body = $this->replacePlaceholders($body, $recipient, '');

        $mail->setBody($body, 'text/html');
        $mail->send();
        return true;
    }

    /**
     * @param string $body
     * @param array $recipient
     * @param string $nodeUri
     * @return string
     */
    public function replacePlaceholders(string $body, array $recipient, string $nodeUri):string
    {
        $salutation = '';

        if($recipient['customsalutation']) {
            if($recipient['customsalutation']) {
                $salutation = $recipient['customsalutation'];
                $body = str_replace('{firstname}', '', $body);
                $body = str_replace('{lastname}', '', $body);
            }
        } else {
            if(array_key_exists('gender', $recipient)) {
                $salutation = $this->getSalutationFromTranslations($recipient['gender']);
            }
            $body = str_replace('{firstname}', $recipient['firstname'], $body);
            $body = str_replace('{lastname}', $recipient['lastname'], $body);
        }

        $unsubscribeUri = $this->settings['baseUri'] . '/unsubscribe/'. $recipient['identifier'];

        $body = str_replace('{unsubscribe}', '<a href="' . $unsubscribeUri . '" target="_blank">' . $this->translator->translateById('unsubscribe', [], null, null, $sourceName = 'Mail/Unsubscribe', $packageKey = 'NeosRulez.DirectMail') . '</a>', $body);
        $body = str_replace('{pageurl}', $nodeUri, $body);
        $body = str_replace('{salutation}', $salutation, $body);
        if(array_key_exists('queueIdentifier', $recipient)) {
            $body = str_replace('{queue}', $recipient['queueIdentifier'], $body);
        }
        if(array_key_exists('recipientIdentifier', $recipient)) {
            $body = str_replace('{recipient}', $recipient['recipientIdentifier'], $body);
        }
        return $body;
    }

    /**
     * @param bool $gender
     * @return string
     */
    public function getSalutationFromTranslations(bool $gender):string
    {
        $salutation = $this->translator->translateById(($gender == 1 ? 'salutation.1' : ($gender == 2 ? 'salutation.2' : ($gender == 3 ? 'salutation.3' : 'salutation.3'))), [], null, null, $sourceName = 'Mail/Salutation', $packageKey = 'NeosRulez.DirectMail');
        return $salutation;
    }

}
