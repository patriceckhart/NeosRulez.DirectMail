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
     * @Flow\Inject
     * @var SlotService
     */
    protected $slotService;

    /**
     * @return string
     */
    public function execute():string
    {
        $queues = $this->queueRepository->findOpenQueues();
        $totalRecipients = 0;
        $totalRecipientLists = 0;
        $sentMails = 0;
        $removed = 0;
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

                            if($this->slotService->processQueueRecipients($recipient)) {
                                $recipientData = ['email' => $recipient->getEmail(), 'dimensions' => $recipient->getDimensions(), 'customFields' => $recipient->getCustomFields(), 'firstname' => $recipient->getFirstname(), 'lastname' => $recipient->getLastname(), 'gender' => $recipient->getGender(), 'customsalutation' => $recipient->getCustomsalutation(), 'recipientIdentifier' => $this->persistenceManager->getIdentifierByObject($recipient), 'queueIdentifier' => $this->persistenceManager->getIdentifierByObject($queue), 'identifier' => $this->persistenceManager->getIdentifierByObject($recipient)];
                                $sent = $this->mailService->execute($queue->getNodeuri(), $recipientData, $queue->getName());
                                if ($sent) {
                                    $queueRecipient->setSent(true);
                                    $this->queueRecipientRepository->update($queueRecipient);
                                    $sentMails = $sentMails + 1;
                                } else {
                                    $this->queueRecipientRepository->remove($queueRecipient);
                                    $removed = $removed + 1;
                                    $totalQueueRecipients = $totalQueueRecipients - 1;
                                }
                            } else {
                                $this->queueRecipientRepository->remove($queueRecipient);
                            }

                            $this->persistenceManager->persistAll();
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
        }
        return 'Total recipient lists: ' . $totalRecipientLists . "\n" . 'Total recipients: ' . $totalRecipients . "\n" . 'Removed recipients: ' . $removed . "\n" . 'Sent mails: ' . $sentMails;;
    }

}
