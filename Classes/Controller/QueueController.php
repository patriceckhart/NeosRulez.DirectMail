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
use NeosRulez\DirectMail\Domain\Model\Queue;
use NeosRulez\DirectMail\Domain\Model\RecipientList;
use NeosRulez\DirectMail\Domain\Service\SlotService;

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
     * @var \NeosRulez\DirectMail\Domain\Service\MergeService
     */
    protected $mergeService;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\EcgService
     */
    protected $ecgService;

    /**
     * @Flow\Inject
     * @var SlotService
     */
    protected $slotService;


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
            $queueRecipientsNotSent = $this->queueRecipientRepository->findByQueueAndNotSent($queue);
            $queueRecipientsSent = $this->queueRecipientRepository->findByQueueAndSent($queue);
            $isSending = false;
            if($queueRecipientsNotSent->count() > 0) {
                $isSending = true;
            }
            $sent = $queueRecipientsSent->count();

            $queue->isSending = $isSending;
            $queue->sent = $sent;
            $queue->tosend = $queueRecipients->count();
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
        $result = [];
        $trackings = $this->trackingRepository->findOpenedByQueue($queue);
        foreach ($trackings as $tracking) {
            if($tracking->getRecipient() !== null && array_key_exists($tracking->getRecipient()->getEmail(), $result)) {
                $result[$tracking->getRecipient()->getEmail()]['opened'] = ($result[$tracking->getRecipient()->getEmail()]['opened'] + 1);
            } else {
                if ($tracking->getRecipient() !== null) {
                    $result[$tracking->getRecipient()->getEmail()] = [
                        'tracking' => $tracking,
                        'opened' => 1
                    ];
                }
            }
        }
        $sortField = array_column($result, 'opened');
        array_multisort($sortField, SORT_ASC, $result);
        $result = array_values($result);
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
                    'title' => $mailing->getProperty('title'),
                    'backendTitle' => $mailing->hasProperty('backendTitle') ? $mailing->getProperty('backendTitle') : '',
                    'identifier' => $mailing->getIdentifier(),
                    'replyTo' => $mailing->hasProperty('replyTo') ? $mailing->getProperty('replyTo') : '',
                ];
            }
        }
        $this->view->assign('nodes', $result);
        $recipientLists = $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('name' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        if($recipientLists->count() > 0) {
            foreach ($recipientLists as $recipientList) {
                $recipientList->recipientCount = count($this->recipientRepository->findByRecipientList($recipientList));
            }
        }
        $this->view->assign('recipientLists', $recipientLists);
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $newQueue
     * @return void
     */
    public function createAction($newQueue)
    {
        $recipientLists = $newQueue->getRecipientlist();
        $recipients = [];
        foreach ($recipientLists as $recipientList) {
            $recipientListRecipients = $this->recipientRepository->findActiveByRecipientList($recipientList);
            if(!empty($recipientListRecipients)) {
                foreach ($recipientListRecipients as $recipientListRecipient) {
                    $recipients[] = $recipientListRecipient;
                }
            }
        }
        $this->queueRepository->add($newQueue);

        $uniqueRecipients = $this->ecgService->compareWithEcgList($this->mergeService->uniqueRecipients($recipients));

        if(!empty($uniqueRecipients)) {
            foreach ($uniqueRecipients as $uniqueRecipient) {

                if($this->slotService->addRecipientToQueue($uniqueRecipient)) {
                    $queueRecipient = new \NeosRulez\DirectMail\Domain\Model\QueueRecipient();
                    $queueRecipient->setRecipient($uniqueRecipient);
                    $queueRecipient->setQueue($newQueue);
                    $addToQueue = $this->nodeService->nodeUri($newQueue->getNodeuri(), ['dimensions' => $uniqueRecipient->getDimensions()]);
                    if($addToQueue) {
                        $this->queueRecipientRepository->add($queueRecipient);
                    }
                }
            }
        }

        $this->persistenceManager->persistAll();

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
     * @param bool $redirect
     * @return void
     */
    public function deleteAction($queue, bool $redirect = true)
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
        if($redirect) {
            $this->redirect('index', 'queue');
        }
    }

    /**
     * @return void
     */
    public function flushAction()
    {
        $queues = $this->queueRepository->findAll();
        foreach ($queues as $queue) {
            $this->deleteAction($queue, false);
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
     * @param Queue $queue
     * @return void
     */
    public function recipientListAction(Queue $queue): void
    {
        $this->view->assign('queue', $queue);
    }

    /**
     * @param RecipientList $newRecipientList
     * @param Queue $queue
     * @param int $option // 1=opened, 2=notOpened 3=all 4=count
     * @param int $count
     * @return void
     */
    public function createRecipientListAction(RecipientList $newRecipientList, Queue $queue, int $option, int $count): void
    {

        $trackings = $this->trackingRepository->findByQueue($queue);
        $recipientLists = $queue->getRecipientlist();
        $recipients = [];

        if($option === 3 || $option === 2) {
            foreach ($recipientLists as $recipientList) {
                $recipientListRecipients = $this->recipientRepository->findByRecipientList($recipientList);
                foreach ($recipientListRecipients as $recipientListRecipient) {
                    $recipients[$recipientListRecipient->getIdentifier()] = $recipientListRecipient;
                }
            }
        }

        foreach ($trackings as $tracking) {
            if($option === 1 && $tracking->getAction() === 'opened') {
                $recipients[$tracking->getRecipient()->getIdentifier()] = $tracking->getRecipient();
            }
            if($option === 2) {
                if(array_key_exists($tracking->getRecipient()->getIdentifier(), $recipients)) {
                    unset($recipients[$tracking->getRecipient()->getIdentifier()]);
                }
            }
            if($option === 4) {
                if($this->trackingRepository->countByQueueAndRecipient($queue, $tracking->getRecipient()) >= $count) {
                    $recipients[$tracking->getRecipient()->getIdentifier()] = $tracking->getRecipient();
                }
            }
        }

        $recipients = array_values($recipients);

        $this->recipientListRepository->add($newRecipientList);
        $this->persistenceManager->persistAll();

        foreach ($recipients as $recipient) {
            $recipientLists = $recipient->getRecipientlist();
            $recipientLists->add($newRecipientList);
            $recipient->setRecipientlist($recipientLists);
            $this->recipientRepository->update($recipient);
        }

        $this->redirect('index', 'recipientList');
    }

    /**
     * @return string
     */
    public function getNodeUri($node): string
    {
        return $this->linkingService->createNodeUri(
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
    }

}
