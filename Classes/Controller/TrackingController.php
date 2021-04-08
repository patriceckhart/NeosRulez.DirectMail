<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

class TrackingController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\TrackingRepository
     */
    protected $trackingRepository;

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
     * @return void
     */
    public function indexAction()
    {
        $this->redirect('tracking', 'queue');
    }

    /**
     * @param string $queue
     * @param string $recipient
     * @param string $action
     * @param string $description
     * @return boolean
     */
    public function createAction(string $queue, string $recipient, string $action = 'opened', string $description = ''):bool
    {
        $newTracking = new \NeosRulez\DirectMail\Domain\Model\Tracking();
        $newTracking->setQueue($this->queueRepository->findQueueByIdentifier($queue));
        $newTracking->setRecipient($this->recipientRepository->findRecipientByIdentifier($recipient));
        $newTracking->setAction($action);
        $newTracking->setDescription($description);
        $this->trackingRepository->add($newTracking);
        $this->persistenceManager->persistAll();
        $this->response->setStatusCode(200);
        return false;
    }

}
