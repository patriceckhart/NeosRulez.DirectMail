<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

class SubscriptionController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientRepository
     */
    protected $recipientRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MailService
     */
    protected $mailService;


    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $newRecipient
     * @return void
     */
    public function createAction($newRecipient)
    {
        $this->recipientRepository->add($newRecipient);
        $recipientData = [
            'gender' => $newRecipient->getGender(),
            'firstname' => $newRecipient->getFirstname(),
            'lastname' => $newRecipient->getLastname(),
            'email' => $newRecipient->getEmail(),
            'identifier' => $this->persistenceManager->getIdentifierByObject($newRecipient)
        ];
        $this->mailService->sendDoubleOptIn($recipientData);
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function setActiveAction(string $identifier)
    {
        $recipient = $this->recipientRepository->findByIdentifier($identifier);
        $recipient->setActive(true);
        $this->recipientRepository->update($recipient);
        $this->persistenceManager->persistAll();
    }

    /**
     * @param string $identifier
     * @param string $recipientList
     * @return void
     */
    public function unsubscribeAction(string $identifier, string $recipientList)
    {
        $recipient = $this->recipientRepository->findByIdentifier($identifier);
        $recipientLists = $recipient->getRecipientList();
        $newRecipientLists = [];
        foreach ($recipientLists as $list) {
            $listIdentifier = $this->persistenceManager->getIdentifierByObject($list);
            if($listIdentifier != $recipientList) {
                $newRecipientLists[] = $list;
            }
        }
        if(!empty($newRecipientLists)) {
            $recipient->setRecipientList($newRecipientLists);
        } else {
            $recipient->setActive(false);
        }
        $this->recipientRepository->update($recipient);
        $this->persistenceManager->persistAll();
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @return void
     */
    public function deleteAction($recipient)
    {
        $this->recipientRepository->remove($recipient);
        $this->persistenceManager->persistAll();
        $this->redirect('index', 'recipient');
    }

}
