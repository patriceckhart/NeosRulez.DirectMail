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
        $recipients = $this->recipientRepository->findAll();
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
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll());
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $newRecipient
     * @return void
     */
    public function createAction($newRecipient)
    {
        $newRecipient->setActive(true);
        $this->recipientRepository->add($newRecipient);
        $this->redirect('index', 'recipient');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @return void
     */
    public function editAction($recipient)
    {
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll());
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
