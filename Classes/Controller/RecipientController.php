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
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\TrackingRepository
     */
    protected $trackingRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\QueueRecipientRepository
     */
    protected $queueRecipientRepository;


    /**
     * @param integer $offset
     * @param integer $length
     * @param integer $itemsPerLoad
     * @param integer $page
     * @param string $searchstring
     * @param boolean $filterInactive
     * @return void
     */
    public function indexAction(int $offset = 0, int $length = 50, int $itemsPerLoad = 50, int $page = 1, string $searchstring = '', bool $filterInactive = false)
    {
        $recipients = $this->recipientRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING, 'email' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        if($searchstring != '') {
            $recipients = $this->recipientRepository->findBySearchstring($searchstring);
        }
        if($filterInactive) {
            $recipients = $this->recipientRepository->findInactiveRecipients();
            $this->view->assign('hideFilter', true);
        }
        $combinedRecipients = [];
        if($recipients) {
            foreach ($recipients as $recipient) {
                $recipient->identifier = $this->persistenceManager->getIdentifierByObject($recipient);
                $combinedRecipients[] = $recipient;
            }
            $count = count($recipients);

            $offset = $page > 1 ? (($page - 1) * $itemsPerLoad) : $offset;
            $result = array_slice($combinedRecipients, $offset, $length);

            $this->view->assign('offset', ($offset + $itemsPerLoad));
            $this->view->assign('length', $itemsPerLoad);
            $this->view->assign('count', $count);

            $pages = ceil($count / $itemsPerLoad);
            $pagination = [];
            if($pages > 1) {
                for ($i = 1; $i <= $pages; $i++) {
                    $pagination[] = $i;
                }
            }
            $this->view->assign('pages', $pages);
            $this->view->assign('pagination', $pagination);
            $this->view->assign('page', $page);

        }
        $this->view->assign('recipients', $result);
        $this->view->assign('action', 'list');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function newAction(\NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        if($selectedRecipientList !== null) {
            $this->view->assign('selectedRecipientList', $this->persistenceManager->getIdentifierByObject($selectedRecipientList));
        }
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute());
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $newRecipient
     * @param string $selectedRecipientList
     * @return void
     */
    public function createAction(\NeosRulez\DirectMail\Domain\Model\Recipient $newRecipient, string $selectedRecipientList = '')
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
        if($selectedRecipientList != '') {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $selectedRecipientList));
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function editAction($recipient, \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        if($selectedRecipientList !== null) {
            $this->view->assign('selectedRecipientList', $this->persistenceManager->getIdentifierByObject($selectedRecipientList));
        }
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute());
        $this->view->assign('recipient', $recipient);
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @param string $selectedRecipientList
     * @return void
     */
    public function updateAction($recipient, string $selectedRecipientList = '')
    {
        $this->recipientRepository->update($recipient);
        if($selectedRecipientList != '') {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $selectedRecipientList));
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function changeAction($recipient, \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        if($recipient->getActive() === true) {
            $recipient->setActive(false);
        } else {
            $recipient->setActive(true);
        }
        $this->recipientRepository->update($recipient);

        if($selectedRecipientList !== null) {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)));
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Recipient $recipient
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function deleteAction($recipient, \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        $trackings = $this->trackingRepository->findByRecipient($recipient);
        if(!empty($trackings)) {
            foreach ($trackings as $tracking) {
                $this->trackingRepository->remove($tracking);
                $this->persistenceManager->persistAll();
            }
        }

        $queueRecipients = $this->queueRecipientRepository->findByRecipient($recipient);
        if(!empty($queueRecipients)) {
            foreach ($queueRecipients as $queueRecipient) {
                $this->queueRecipientRepository->remove($queueRecipient);
                $this->persistenceManager->persistAll();
            }
        }

        $this->recipientRepository->remove($recipient);
        $this->persistenceManager->persistAll();

        if($selectedRecipientList !== null) {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)));
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param array $recipients
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function activateSelectedAction(array $recipients, \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            $recipientObject->setActive(true);
            $this->recipientRepository->update($recipientObject);
        }

        if($selectedRecipientList !== null) {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)));
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param array $recipients
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function deactivateSelectedAction(array $recipients, \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            $recipientObject->setActive(false);
            $this->recipientRepository->update($recipientObject);
        }

        if($selectedRecipientList !== null) {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)));
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param array $recipients
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList
     * @return void
     */
    public function deleteSelectedAction(array $recipients, \NeosRulez\DirectMail\Domain\Model\RecipientList $selectedRecipientList = null)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);

            $trackings = $this->trackingRepository->findByRecipient($recipientObject);
            if(!empty($trackings)) {
                foreach ($trackings as $tracking) {
                    $this->trackingRepository->remove($tracking);
                    $this->persistenceManager->persistAll();
                }
            }

            $queueRecipients = $this->queueRecipientRepository->findByRecipient($recipientObject);
            if(!empty($queueRecipients)) {
                foreach ($queueRecipients as $queueRecipient) {
                    $this->queueRecipientRepository->remove($queueRecipient);
                    $this->persistenceManager->persistAll();
                }
            }

            $this->recipientRepository->remove($recipientObject);
        }

        if($selectedRecipientList !== null) {
            $this->redirect('edit','recipientList',Null,array('recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)));
        }
        $this->redirect('index', 'recipient');
    }

}
