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
     * @var \NeosRulez\DirectMail\Domain\Repository\QueueRecipientRepository
     */
    protected $queueRecipientRepository;

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
        $totalRecipients = 0;
        $totalRecipientLists = 0;
        $sentMails = 0;
        if($queues) {
            foreach ($queues as $queue) {
                $totalRecipientLists = $totalRecipientLists + count($queue->getRecipientList());
                $queueRecipients = $this->queueRecipientRepository->findByQueue($queue);
                if (!empty($queueRecipients)) {
                    $totalQueueRecipients = count($queueRecipients);
                    foreach ($queueRecipients as $queueRecipient) {
                        $totalRecipients = $totalRecipients + 1;
                        if(!$queueRecipient->getSent()) {
                            $recipient = $queueRecipient->getRecipient();
                            $recipientData = ['email' => $recipient->getEmail(), 'firstname' => $recipient->getFirstname(), 'lastname' => $recipient->getLastname(), 'gender' => $recipient->getGender(), 'customsalutation' => $recipient->getCustomsalutation(), 'recipientIdentifier' => $this->persistenceManager->getIdentifierByObject($recipient), 'recipientList' => $this->persistenceManager->getIdentifierByObject($queueRecipient->getRecipientList()), 'queueIdentifier' => $this->persistenceManager->getIdentifierByObject($queue), 'identifier' => $this->persistenceManager->getIdentifierByObject($recipient)];
                            $sent = $this->mailService->execute($queue->getNodeuri(), $recipientData, $queue->getName());
                            if ($sent) {
                                $queueRecipient->setSent(true);
                                $this->queueRecipientRepository->update($queueRecipient);
                                $this->persistenceManager->persistAll();
                                $sentMails = $sentMails + 1;
                            }
                        }
                    }

                    $totalSentQueueRecipients = count($this->queueRecipientRepository->findOpenQueueRecipients($queue));
                    if($totalSentQueueRecipients == $totalQueueRecipients) {
                        $queue->setDone(true);
                        $this->queueRepository->update($queue);
                        $this->persistenceManager->persistAll();
                    }

                }
            }
            $result = 'Total recipient lists: ' . $totalRecipientLists . "\n" . 'Total recipients: ' . $totalRecipients . "\n" . 'Sent mails: ' . $sentMails;
        }
        return $result;
    }

}
