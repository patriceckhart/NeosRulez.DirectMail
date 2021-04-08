<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

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
     * @return void
     */
    public function indexAction()
    {
        $recipientLists = $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
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
     * @return void
     */
    public function editAction($recipientList)
    {
        $recipients = $this->recipientRepository->findByRecipientList($recipientList);
        if($recipients) {
            foreach ($recipients as $recipient) {
                $recipient->identifier = $this->persistenceManager->getIdentifierByObject($recipient);
            }
        }
        $this->view->assign('recipients', $recipients);
        $this->view->assign('recipientList', $recipientList);
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
        $this->recipientListRepository->remove($recipientList);
        $this->persistenceManager->persistAll();
        $this->redirect('index', 'recipientList');
    }

}
