<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;
use NeosRulez\DirectMail\Domain\Repository\QueueRecipientRepository;
use NeosRulez\DirectMail\Domain\Repository\QueueRepository;
use NeosRulez\DirectMail\Domain\Repository\TrackingRepository;

class RecipientListController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientListRepository
     */
    protected $recipientListRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientRepository
     */
    protected $recipientRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\ImportRepository
     */
    protected $importRepository;

    /**
     * @Flow\Inject
     * @var QueueRecipientRepository
     */
    protected $queueRecipientRepository;

    /**
     * @Flow\Inject
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @Flow\Inject
     * @var TrackingRepository
     */
    protected $trackingRepository;


    /**
     * @return void
     */
    public function indexAction()
    {
        $recipientLists = $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('name' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        $result = [];
        foreach ($recipientLists as $i => $recipientList) {
            $recipientList->count = $this->recipientRepository->countByRecipientList($recipientList);
            $result[] = $recipientList;
        }
        $this->view->assign('recipientLists', $result);
    }

    /**
     * @return void
     */
    public function newAction()
    {

    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $newRecipientList
     * @return void
     */
    public function createAction($newRecipientList)
    {
        $this->recipientListRepository->add($newRecipientList);
        $this->redirect('index', 'recipientList');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @param integer $offset
     * @param integer $length
     * @param integer $itemsPerLoad
     * @param integer $page
     * @param string $searchstring
     * @param boolean $filterInactive
     * @return void
     */
    public function editAction(\NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList, int $offset = 0, int $length = 50, int $itemsPerLoad = 50, int $page = 1, string $searchstring = '', bool $filterInactive = false)
    {
        $recipients = $this->recipientRepository->findByRecipientList($recipientList)->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING, 'email' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        if($searchstring != '') {
            $recipients = $this->recipientRepository->findByRecipientListAndSearchstring($recipientList, $searchstring);
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
        $this->view->assign('recipientList', $recipientList);
        $this->view->assign('action', 'edit');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @return void
     */
    public function updateAction($recipientList)
    {
        $this->recipientListRepository->update($recipientList);
        $this->redirect('index', 'recipientList');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\RecipientList $recipientList
     * @return void
     */
    public function deleteAction($recipientList)
    {
        $imports = $this->importRepository->findByRecipientlist($recipientList);
        if($imports->count() > 0) {
            foreach ($imports as $import) {
                $this->importRepository->remove($import);
            }
        }

        $recipients = $this->recipientRepository->findByRecipientlist($recipientList);
        foreach ($recipients as $recipient) {
            $newRecipientLists = new ArrayCollection();
            $recipientLists = $recipient->getRecipientlist();
            foreach ($recipientLists as $list) {
                if($list !== $recipientList) {
                    $newRecipientLists->add($list);
                }
            }

            $queues = $this->queueRepository->findByRecipientlist($recipientList);
            foreach ($queues as $queue) {

                $queueRecipients = $this->queueRecipientRepository->findByQueue($queue);
                foreach ($queueRecipients as $queueRecipient) {
                    $this->queueRecipientRepository->remove($queueRecipient);
                    $this->persistenceManager->persistAll();
                }

                $trackings = $this->trackingRepository->findByQueue($queue);
                foreach ($trackings as $tracking) {
                    $this->trackingRepository->remove($tracking);
                    $this->persistenceManager->persistAll();
                }

                $queue->setRecipientlist((new ArrayCollection()));
                $this->queueRepository->update($queue);
                $this->queueRepository->remove($queue);
                $this->persistenceManager->persistAll();
            }

            if($newRecipientLists->isEmpty()) {
                $this->recipientRepository->remove($recipient);
                $this->persistenceManager->persistAll();
            } else {
                $recipient->setRecipientlist($newRecipientLists);
                $this->recipientRepository->update($recipient);
                $this->persistenceManager->persistAll();
            }
        }

        $this->recipientListRepository->remove($recipientList);
        $this->persistenceManager->persistAll();
        $this->redirect('index', 'recipientList');
    }

}
