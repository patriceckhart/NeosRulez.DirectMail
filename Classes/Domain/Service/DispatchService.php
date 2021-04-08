<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @Flow\Scope("singleton")
 */
class DispatchService {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\QueueRepository
     */
    protected $queueRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientRepository
     */
    protected $recipientRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MailService
     */
    protected $mailService;


    /**
     * @return string
     */
    public function execute():string
    {
        $queues = $this->queueRepository->findOpenQueues();
        $active = 0;
        $inactive = 0;
        $totalRecipients = 0;
        $totalRecipientLists = 0;
        if($queues) {
            foreach ($queues as $queue) {
                $recipientLists = $queue->getRecipientList();
                if($recipientLists) {
                    foreach ($recipientLists as $recipientList) {
                        $totalRecipientLists = $totalRecipientLists + 1;
                        $recipients = $this->recipientRepository->findByRecipientList($recipientList);
                        if($recipients) {
                            foreach ($recipients as $recipient) {
                                $totalRecipients = $totalRecipients + 1;
                                $isActive = $recipient->getActive();
                                if($isActive) {
                                    $active = $active + 1;
                                    $recipientData = ['email' => $recipient->getEmail(), 'firstname' => $recipient->getFirstname(), 'lastname' => $recipient->getLastname(), 'gender' => $recipient->getGender(), 'customsalutation' => $recipient->getCustomsalutation(), 'recipientList' => $this->persistenceManager->getIdentifierByObject($recipientList), 'identifier' => $this->persistenceManager->getIdentifierByObject($recipient)];
                                    $sent = $this->mailService->execute($queue->getNodeuri(), $recipientData, $queue->getName());
                                    if($sent) {
                                        $this->updateQueue($queue);
                                    }
                                } else {
                                    $inactive = $inactive + 1;
                                }
                            }
                        }
                    }
                }
            }
            $result = 'Total recipient lists: ' . $totalRecipientLists . "\n" . 'Total recipients: ' . $totalRecipients . "\n" . 'Sent mails: ' . $active . "\n" . 'Inactive recipients: ' . $inactive;
        }
        return $result;
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return void
     */
    public function updateQueue(\NeosRulez\DirectMail\Domain\Model\Queue $queue) {
        $sent = $queue->getSent();
        $queue->setSent($sent + 1);
        $this->queueRepository->update($queue);
    }

}
