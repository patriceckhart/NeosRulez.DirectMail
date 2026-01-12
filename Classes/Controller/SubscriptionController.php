<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Fusion\View\FusionView;
use NeosRulez\DirectMail\Domain\Model\Recipient;
use NeosRulez\DirectMail\Domain\Model\RecipientList;

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
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientListRepository
     */
    protected $recipientListRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MailService
     */
    protected $mailService;

    /**
     * @param array $newRecipient
     * @return void
     */
    public function createAction(array $newRecipient): void
    {
        $recipient = new Recipient();
        $recipient->setGender($newRecipient['gender']);
        $recipient->setFirstname($newRecipient['firstname']);
        $recipient->setLastname($newRecipient['lastname']);
        $recipient->setEmail($newRecipient['email']);
        $recipientLists = new ArrayCollection();
        foreach ($newRecipient['recipientlist'] as $item) {
            $recipientLists->add($this->recipientListRepository->findByIdentifier($item));
        }
        $recipient->setRecipientlist($recipientLists);
        $this->recipientRepository->add($recipient);
        $recipientData = [
            'gender' => $recipient->getGender(),
            'firstname' => $recipient->getFirstname(),
            'lastname' => $recipient->getLastname(),
            'email' => $recipient->getEmail(),
            'identifier' => $this->persistenceManager->getIdentifierByObject($recipient)
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
     * @return void
     */
    public function unsubscribeAction(string $identifier)
    {
        $recipient = $this->recipientRepository->findByIdentifier($identifier);
        $this->view->assign('recipient', $recipient);
        $this->view->assign('identifier', $identifier);
    }

    /**
     * @return void
     */
    public function unsubscribeRecipientAction()
    {
        $recipient = $this->recipientRepository->findByIdentifier($this->request->getArgument('recipient'));
        $lists = [];
        if(!empty($this->request->getArgument('recipientLists'))) {
            foreach ($this->request->getArgument('recipientLists') as $recipientList) {
                $lists[] = $this->recipientListRepository->findByIdentifier($recipientList);
            }
            $recipient->setRecipientlist($lists);
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
