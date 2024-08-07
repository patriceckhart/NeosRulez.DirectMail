<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\I18n;
use Neos\Flow\I18n\Locale;

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
     * @var \NeosRulez\DirectMail\Domain\Service\NodeService
     */
    protected $nodeService;

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
     * @Flow\Inject
     * @var I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="contentDimensions")
     * @var array
     */
    protected $contentDimensions;

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

            $uriForEncode = $this->nodeService->nodeUri($nodeUri, $recipient);

            if(!$uriForEncode) {
                return false;
            }

            $senderName = $this->settings['senderName'];
            if(array_key_exists('senderName', $uriForEncode) && $uriForEncode['senderName']) {
                if ($uriForEncode['senderName'] !== '') {
                    $senderName = $uriForEncode['senderName'];
                }
            }

            $mail = new \Neos\SwiftMailer\Message();
            $mail
                ->setFrom([$this->settings['senderMail'] => $senderName])
                ->setTo([$email => $recipient['firstname'] . ' ' . $recipient['lastname']]);

            if(array_key_exists('replyTo', $uriForEncode) && $uriForEncode['replyTo']) {
                if (filter_var($uriForEncode['replyTo'], FILTER_VALIDATE_EMAIL)) {
                    $replyTo = str_replace(' ', '', $uriForEncode['replyTo']);
                    $mail->setReplyTo([$replyTo => $replyTo]);
                }
            }

            $http = strpos($uriForEncode['nodeUri'], 'https:');
            $https = strpos($uriForEncode['nodeUri'], 'https:');
            if($http !== false || $https !== false) {
                $uriForEncode['nodeUri'] = parse_url($uriForEncode['nodeUri'], PHP_URL_PATH);
            }
            $renderUri = $this->settings['baseUri'] . '/directmail/' . base64_encode($this->settings['baseUri'] . $uriForEncode['nodeUri']);
            $file = $this->getPageContent($renderUri);

            $body = $this->replacePlaceholders($file, $recipient, $nodeUri);

            $mail->setSubject($uriForEncode['subject']);
            $mail->setBody($body, 'text/html');

            if(array_key_exists('attachments', $uriForEncode)) {
                if(!empty($uriForEncode['attachments'])) {
                    foreach ($uriForEncode['attachments'] as $attachment) {
                        $mail->attach(new \Swift_Attachment(file_get_contents($attachment['temporaryLocalCopyFilename']), $attachment['mailFilename'], $attachment['mediaType']));
                    }
                }
            }

            $mail->send();
        }
        return true;
    }

    /**
     * @param string $uri
     * @return string
     */
    public function getPageContent(string $uri): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => false
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
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

        if(array_key_exists('dimensions', $recipient) && $recipient['dimensions'] !== null) {
            if (array_key_exists('language', $this->contentDimensions)) {
                if (array_key_exists('presets', $this->contentDimensions['language'])) {
                    $presets = $this->contentDimensions['language']['presets'];
                    if (array_key_exists('language', $recipient['dimensions'])) {
                        if (array_key_exists($recipient['dimensions']['language'], $presets)) {
                            $locale = new Locale($recipient['dimensions']['language']);
                            $this->localizationService->getConfiguration()->setCurrentLocale($locale);
                        }
                    }
                }
            }
        }

        if(array_key_exists('customFields', $recipient) && !empty($recipient['customFields'])) {
            foreach ($recipient['customFields'] as $customFieldIterator => $customField) {
                if($customField !== '') {
                    $body = str_replace('{' . $customFieldIterator . '}', $customField, $body);
                }
            }
        }

        $salutation = '';

        if(array_key_exists('customsalutation', $recipient) && $recipient['customsalutation']) {
            $salutation = $recipient['customsalutation'];
            $body = str_replace('{firstname}', '', $body);
            $body = str_replace('{lastname}', '', $body);
        } else {
            if(array_key_exists('gender', $recipient)) {
                $salutation = $this->getSalutationFromTranslations($recipient['gender']);
            }
            $body = str_replace('{firstname}', $recipient['firstname'], $body);
            $body = str_replace('{lastname}', $recipient['lastname'], $body);
        }

        $body = str_replace('{email}', $recipient['email'], $body);
        $body = str_replace('%7Bemail%7D', $recipient['email'], $body);

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
     * @param int $gender
     * @return string
     */
    public function getSalutationFromTranslations(int $gender):string
    {
        $salutation = $this->translator->translateById(($gender == 1 ? 'salutation.1' : ($gender == 2 ? 'salutation.2' : ($gender == 3 ? 'salutation.3' : 'salutation.3'))), [], null, null, $sourceName = 'Mail/Salutation', $packageKey = 'NeosRulez.DirectMail');
        return $salutation;
    }

}
