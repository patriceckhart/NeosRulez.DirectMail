<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @Flow\Scope("singleton")
 */
class MergeService {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientRepository
     */
    protected $recipientRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\QueueRecipientRepository
     */
    protected $queueRecipientRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\TrackingRepository
     */
    protected $trackingRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * @return void
     */
    public function execute():void
    {
        $recipients = $this->recipientRepository->findAll();
        $merged = [];
        $recipientLists = [];
        $uniqueRecipients = [];
        if($recipients) {
            foreach ($recipients as $recipient) {
                $recipientLists[$recipient->getEmail()][] = $recipient->getRecipientlist();
            }
            foreach ($recipientLists as $i => $recipientList) {
                foreach ($recipientList as $lists) {
                    foreach ($lists as $list) {
                        $merged[$i][] = $list;
                    }
                }
            }
            foreach ($recipients as $recipient) {
                $uniqueRecipients[$recipient->getEmail()] = true;
            }
            foreach ($uniqueRecipients as $i => $uniqueRecipient) {
                $recipient = $this->recipientRepository->findByEmail($i)->getFirst();
                if(!empty($merged[$recipient->getEmail()])) {
                    $recipient->setRecipientlist($merged[$recipient->getEmail()]);
                    $this->recipientRepository->update($recipient);
                    $this->deleteObsolete($recipient->getEmail());
                } else {
                    $this->deleteObsoleteRecipientQueue($recipient);
                    $this->deleteObsoleteTracking($recipient);
                    $this->recipientRepository->remove($recipient);
                }
            }
        }
    }

    /**
     * @param string $email
     * @return void
     */
    public function deleteObsolete(string $email):void
    {
        $recipients = $this->recipientRepository->findByEmail($email);
        $i = 0;
        if($recipients) {
            foreach ($recipients as $recipient) {
                if($i != 0) {
                    $this->deleteObsoleteRecipientQueue($recipient);
                    $this->deleteObsoleteTracking($recipient);
                    $this->recipientRepository->remove($recipient);
                }
                $i = $i + 1;
            }
        }
    }

    /**
     * @param mixed $recipient
     * @return void
     */
    public function deleteObsoleteRecipientQueue($recipient):void
    {
        $queueRecipients = $this->queueRecipientRepository->findByRecipient($recipient);
        if(!empty($queueRecipients)) {
            foreach ($queueRecipients as $queueRecipient) {
                $this->queueRecipientRepository->remove($queueRecipient);
                $this->persistenceManager->persistAll();
            }
        }
    }

    /**
     * @param mixed $recipient
     * @return void
     */
    public function deleteObsoleteTracking($recipient):void
    {
        $trackings = $this->trackingRepository->findByRecipient($recipient);
        if(!empty($trackings)) {
            foreach ($trackings as $tracking) {
                $this->trackingRepository->remove($tracking);
                $this->persistenceManager->persistAll();
            }
        }
    }

    /**
     * @param mixed $recipients
     * @return array
     */
    public function uniqueRecipients($recipients):array
    {
        $items = [];
        $result = [];
        if(!empty($recipients)) {
            foreach ($recipients as $recipient) {
                $items[$recipient->getEmail()] = $recipient;
            }
            if(!empty($items)) {
                foreach ($items as $item) {
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

}
