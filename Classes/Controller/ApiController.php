<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

use Neos\Flow\ResourceManagement\ResourceManager;
use NeosRulez\DirectMail\Domain\Model\RecipientList;
use NeosRulez\DirectMail\Domain\Model\Recipient;

class ApiController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\ImportRepository
     */
    protected $importRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientRepository
     */
    protected $recipientRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientListRepository
     */
    protected $recipientListRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\ResourceManagement\ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MergeService
     */
    protected $mergeService;

    /**
     * @Flow\Inject
     * @var \Neos\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;


    /**
     * @param string $apiKey
     * @param string $data
     * @param RecipientList $list
     * @return string
     */
    public function importAction($apiKey, $data, $list)
    {
        if ((string) $this->settings['apiKey'] !== (string) $apiKey) {
            return json_encode(array('status' => 'error', 'info' => 'Permission denied'));
        }
        $recipients = json_decode($data, true);

        $addedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $deactivatedCount = 0;
        $emailAddresses = array();
        foreach ($recipients as $recipient) {
            $firstname = $recipient['firstname'];
            $lastname = $recipient['lastname'];
            $email = $recipient['email'];
            $gender = $recipient['gender']; // 1 männlich, 2 weiblich, 3 divers
            $customsalutation = $recipient['salutation'];
            
            $email = str_replace(' ', '', $email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $existingRecipient = $this->recipientRepository->findOneRecipientByMail($email);
                if($existingRecipient) {
                    $existingRecipient->setFirstname($firstname);
                    $existingRecipient->setLastname($lastname);
                    $existingRecipient->setEmail($email);
                    $existingRecipient->setGender((int) $gender);
                    $existingRecipient->setCustomsalutation($customsalutation);
                    $existingRecipient->setActive(true);
                    $existingRecipient->setImportedViaApi(true);
                    $recipientLists = $existingRecipient->getRecipientlist();
                    $rawRecipientLists = [];
                    foreach ($recipientLists as $recipientList) {
                        $rawRecipientLists[$this->persistenceManager->getIdentifierByObject($recipientList)] = $recipientList;
                    }
                    $rawRecipientLists[$this->persistenceManager->getIdentifierByObject($list)] = $list;
                    $existingRecipient->setRecipientlist($rawRecipientLists);
                    $this->recipientRepository->update($existingRecipient);
                    $updatedCount++;
                } else {
                    $newRecipient = new Recipient();
                    $newRecipient->setFirstname($firstname);
                    $newRecipient->setLastname($lastname);
                    $newRecipient->setEmail($email);
                    $newRecipient->setGender((int) $gender);
                    $newRecipient->setCustomsalutation($customsalutation);
                    $newRecipient->setActive(true);
                    $newRecipient->setRecipientlist([$list]);
                    $newRecipient->setImportedViaApi(true);
                    $this->recipientRepository->add($newRecipient);
                    $addedCount++;
                }
                $emailAddresses[] = $email;
            } else {
                $skippedCount++;
            }
        }
        $recipientsToSetInactive = $this->recipientRepository->findByActiveAndImportedExcept($emailAddresses);
        foreach ($recipientsToSetInactive as $recipient) {
            $recipient->setActive(false);
            $this->recipientRepository->update($recipient);
            $deactivatedCount++;
        }
        return json_encode(array('status' => 'done', 'counts' => array('added' => $addedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount, 'deactivated' => $deactivatedCount)));
    }
}
