<?php

namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;

/**
 *
 * @Flow\Scope("singleton")
 */
class EcgService
{

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param array $recipients
     * @return array
     */
    public function compareWithEcgList(array $recipients): array
    {
        $result = [];
        if (!empty($recipients)) {
            foreach ($recipients as $recipient) {
                if (!$this->isInList($recipient->getEmail())) {
                    $result[] = $recipient;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $email
     * @return bool
     */
    private function isInList(string $email): bool
    {
        if (array_key_exists('rtrEcgList', $this->settings)) {
            $ecgListApiKey = $this->settings['rtrEcgList']['apiKey'];
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://ecg.rtr.at/dev/api/v1/emails/check/batch',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '
{
   "emails": [
      "' . $email . '"
   ],
   "contained": true
}',
            ]);

            $formattedHeaders = [];
            if (isset($this->settings['curlOptions']['headers']) && is_array($this->settings['curlOptions']['headers'])) {
                foreach ($this->settings['curlOptions']['headers'] as $headerKey => $headerValue) {
                    if (is_int($headerKey) && strpos($headerValue, ':') !== false) {
                        if (str_starts_with(mb_strtolower($headerValue), 'content-type:')) {
                            // Skip Content-Type header as we have a default for it
                            continue;
                        }
                        $formattedHeaders[] = $headerValue;
                    } else {
                        if (mb_strtolower($headerKey) === 'content-type') {
                            // Skip Content-Type header as we have a default for it
                            continue;
                        }
                        $formattedHeaders[] = $headerKey . ': ' . $headerValue;
                    }
                }
            }
            $formattedHeaders[] = 'X-API-KEY: ' . $ecgListApiKey;
            $formattedHeaders[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_HTTPHEADER, $formattedHeaders);

            $response = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($response, true);

            if (
                array_key_exists('emails', $result) &&
                count($result['emails']) === 0
            ) {
                return false;
            }
            return true;
        }
        return false;
    }
}
