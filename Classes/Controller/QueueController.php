<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class QueueController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\QueueRepository
     */
    protected $queueRepository;

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
     * @var \NeosRulez\DirectMail\Domain\Repository\TrackingRepository
     */
    protected $trackingRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\QueueRecipientRepository
     */
    protected $queueRecipientRepository;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * @Flow\Inject
     * @var \Neos\Neos\Service\LinkingService
     */
    protected $linkingService;

    protected function initializeCreateAction() {
        $this->arguments['newQueue']->getPropertyMappingConfiguration()->forProperty('send')->setTypeConverterOption(
            \Neos\Flow\Property\TypeConverter\DateTimeConverter::class, \Neos\Flow\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d\TH:i'
        );
    }

    protected function initializeUpdateAction() {
        $this->arguments['queue']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
            \Neos\Flow\Property\TypeConverter\DateTimeConverter::class, \Neos\Flow\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d\TH:i'
        );
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $queues = $this->queueRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING))->execute();
        $result = [];
        foreach ($queues as $queue) {
            $queueRecipients = $this->queueRecipientRepository->findByQueue($queue);
            $isSending = false;
            $sent = 0;
            if(!empty($queueRecipients)) {
                foreach ($queueRecipients as $queueRecipient) {
                    if(!$queueRecipient->getSent()) {
                        $isSending = true;
                    } else {
                        $sent = $sent + 1;
                    }
                }
            }
            $queue->isSending = $isSending;
            $queue->sent = $sent;
            $queue->tosend = count($queueRecipients);
            $sendPercentage = count($queueRecipients) !== 0 ? (100 / count($queueRecipients)) * $sent : 0;
            $queue->sendPercentage = number_format($sendPercentage, 0);
            $result[] = $queue;
        }
        $this->view->assign('queues', $result);
        $this->view->assign('flowRootPath', constant('FLOW_PATH_ROOT'));
    }

    /**
     * @return void
     */
    public function trackingAction()
    {
        $queues = $this->queueRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING))->execute();
        $result = [];
        if($queues) {
            foreach ($queues as $queue) {
                $queue->opened = $this->trackingRepository->countByQueue($queue);
                $result[] = $queue;
            }
        }
        $this->view->assign('queues', $result);
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return void
     */
    public function indexTrackingAction(\NeosRulez\DirectMail\Domain\Model\Queue $queue)
    {
        $trackings = $this->trackingRepository->findByQueue($queue);
        $trackingsMerged = [];
        $result = [];
        foreach ($trackings as $tracking) {
            $tracking->opened = $this->trackingRepository->countByQueueAndRecipient($tracking->getQueue(), $tracking->getRecipient());
            $trackingsMerged[$tracking->getRecipient()->getEmail()] = $tracking;
        }
        foreach ($trackingsMerged as $trackingMerged) {
            $result[] = ['opened' => $trackingMerged->opened, 'tracking' => $trackingMerged];
        }
        $sortField = array_column($result, 'opened');
        array_multisort($sortField, SORT_ASC, $result);
        $this->view->assign('trackings', $result);
    }

    /**
     * @return void
     */
    public function newAction()
    {
        $context = $this->contextFactory->create();
        $mailings = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.DirectMail:Document.Page]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
        $result = [];
        if($mailings) {
            foreach ($mailings as $mailing) {
                $result[] = [
                    'nodeUri' => $this->getNodeUri($mailing),
                    'title' => $mailing->getProperty('title')
                ];
            }
        }
        $this->view->assign('nodes', $result);
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute());
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $newQueue
     * @return void
     */
    public function createAction($newQueue)
    {
        $recipientLists = $newQueue->getRecipientlist();
        $count = 0;
        $recipients = [];
        foreach ($recipientLists as $recipientList) {
            $count = $count + $this->recipientRepository->countActiveByRecipientList($recipientList);
            $recipients[] = [
                'recipients' => $this->recipientRepository->findActiveByRecipientList($recipientList),
                'recipientList' => $recipientList
            ];
        }
//        $newQueue->setTosend($count);
        $this->queueRepository->add($newQueue);

        if(!empty($recipients)) {
            foreach ($recipients as $recipientItems) {
                if(!empty($recipientItems)) {
                    $recipientItemsRecipientList = $recipientItems['recipientList'];
                    foreach ($recipientItems['recipients'] as $recipientItem) {
                        $queueRecipient = new \NeosRulez\DirectMail\Domain\Model\QueueRecipient();
                        $queueRecipient->setRecipient($recipientItem);
                        $queueRecipient->setQueue($newQueue);
                        $queueRecipient->setRecipientList($recipientItemsRecipientList);
                        $this->queueRecipientRepository->add($queueRecipient);
                    }
                }
            }
        }

        $this->redirect('index', 'queue');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return void
     */
    public function editAction($queue)
    {
        $this->view->assign('queue', $queue);
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return void
     */
    public function updateAction($queue)
    {
        $this->queueRepository->update($queue);
        $this->redirect('index', 'queue');
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $queue
     * @return void
     */
    public function deleteAction($queue)
    {
        $queueRecipients = $this->queueRecipientRepository->findByQueue($queue);
        $trackings = $this->trackingRepository->findByQueue($queue);
        if(!empty($queueRecipients)) {
            foreach ($queueRecipients as $queueRecipient) {
                $this->queueRecipientRepository->remove($queueRecipient);
            }
        }
        if(!empty($trackings)) {
            foreach ($trackings as $tracking) {
                $this->trackingRepository->remove($tracking);
            }
        }
        $this->queueRepository->remove($queue);
        $this->persistenceManager->persistAll();
        $this->redirect('index', 'queue');
    }

    /**
     * @return void
     */
    public function flushAction()
    {
        $queues = $this->queueRepository->findAll();
        foreach ($queues as $queue) {
            $sent = $queue->getSent();
            $toSend = $queue->getTosend();
            if($sent == $toSend || $sent == 0) {
                $trackings = $this->trackingRepository->findByQueue($queue);
                if($trackings) {
                    foreach ($trackings as $tracking) {
                        $this->trackingRepository->remove($tracking);
                    }
                }
                $this->queueRepository->remove($queue);
            }
        }
        $this->persistenceManager->persistAll();
        $this->redirect('index', 'queue');
    }

    /**
     * @return void
     */
    public function startAction()
    {
        shell_exec(constant('FLOW_PATH_ROOT') . 'flow queue:process > /dev/null 2>/dev/null &');
        $this->redirect('index', 'queue');
    }

    /**
     * @return string
     */
    public function getNodeUri($node)
    {
        $url = $this->linkingService->createNodeUri(
            $this->getControllerContext(),
            $node,
            null,
            'html',
            false,
            [],
            '',
            false,
            []
        );
        return $url;
    }

}
