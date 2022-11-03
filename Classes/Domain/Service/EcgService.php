<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

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
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }

    /**
     * @param array $recipients
     * @return array
     */
    public function compareWithEcgList(array $recipients):array
    {
        $result = [];
        if(!empty($recipients)) {
            foreach ($recipients as $recipient) {
                if(!$this->isInList($recipient->getEmail())) {
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
    private function isInList(string $email):bool
    {
        if(array_key_exists('rtrEcgList', $this->settings)) {
            $ecgListApiKey = $this->settings['rtrEcgList']['apiKey'];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://ecg.rtr.at/dev/api/v1/emails/check/batch',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'
{
   "emails": [
      "' . $email . '"
   ],
   "contained": true
}',
                CURLOPT_HTTPHEADER => array(
                    'X-API-KEY: ' . $ecgListApiKey,
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($response, true);
            if(array_key_exists('emails', $result)) {
                if(count($result['emails']) == 0) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

}
