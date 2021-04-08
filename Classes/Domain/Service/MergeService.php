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
                $recipient = $this->recipientRepository->findOneRecipientByMail($i);
                $recipient->setRecipientlist($merged[$recipient->getEmail()]);
                $this->recipientRepository->update($recipient);
                $this->deleteObsolete($recipient->getEmail());
            }
        }
    }

    /**
     * @param string $email
     * @return void
     */
    public function deleteObsolete(string $email):void
    {
        $recipients = $this->recipientRepository->findRecipientsByMail($email);
        $i = 0;
        if($recipients) {
            foreach ($recipients as $recipient) {
                if($i != 0) {
                    $this->recipientRepository->remove($recipient);
                }
                $i = $i + 1;
            }
        }
    }

}
