<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

class RecipientController extends ActionController
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
     * @return void
     */
    public function indexAction()
    {
        $recipients = $this->recipientRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING))->execute();
        if($recipients) {
            foreach ($recipients as $recipient) {
                $recipient->identifier = $this->persistenceManager->getIdentifierByObject($recipient);
            }
        }
        $this->view->assign('recipients', $recipients);
    }

    /**
     * @return void
     */
    public function newAction()
    {
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute());
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $newRecipient
     * @return void
     */
    public function createAction($newRecipient)
    {
        $recipient = $this->recipientRepository->findOneRecipientByMail($newRecipient->getEmail());
        if($recipient) {
            $recipientLists = $recipient->getRecipientlist();
            $rawRecipientLists = [];
            foreach ($recipientLists as $list) {
                $rawRecipientLists[$this->persistenceManager->getIdentifierByObject($list)] = $list;
            }
            foreach ($newRecipient->getRecipientlist() as $newRecipientList) {
                $rawRecipientLists[$this->persistenceManager->getIdentifierByObject($newRecipientList)] = $newRecipientList;
            }

            $recipient->setRecipientlist($rawRecipientLists);

            $recipient->setFirstname($newRecipient->getFirstname());
            $recipient->setLastname($newRecipient->getLastname());
            $recipient->setGender((int) $newRecipient->getGender());
            $recipient->setCustomsalutation($newRecipient->getCustomsalutation());

            $recipient->setActive(true);
            $this->recipientRepository->update($recipient);
        } else {
            $newRecipient->setActive(true);
            $this->recipientRepository->add($newRecipient);
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @return void
     */
    public function editAction($recipient)
    {
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute());
        $this->view->assign('recipient', $recipient);
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @return void
     */
    public function updateAction($recipient)
    {
        $this->recipientRepository->update($recipient);
        $this->redirect('index', 'recipient');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @return void
     */
    public function changeAction($recipient)
    {
        if($recipient->getActive() === true) {
            $recipient->setActive(false);
        } else {
            $recipient->setActive(true);
        }
        $this->recipientRepository->update($recipient);
        $this->redirect('index', 'recipient');
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

    /**
     * @param array $recipients
     * @return void
     */
    public function activateSelectedAction(array $recipients)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            $recipientObject->setActive(true);
            $this->recipientRepository->update($recipientObject);
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param array $recipients
     * @return void
     */
    public function deactivateSelectedAction(array $recipients)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            $recipientObject->setActive(false);
            $this->recipientRepository->update($recipientObject);
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param array $recipients
     * @return void
     */
    public function deleteSelectedAction(array $recipients)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            $this->recipientRepository->remove($recipientObject);
        }
        $this->redirect('index', 'recipient');
    }

}
