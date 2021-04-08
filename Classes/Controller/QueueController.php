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
        $queues = $this->queueRepository->findAll();
        $result = [];
        foreach ($queues as $queue) {
            $recipientLists = $queue->getRecipientlist();
            $count = 0;
            foreach ($recipientLists as $recipientList) {
                $count = $count + $this->recipientRepository->countActiveByRecipientList($recipientList);
            }
            $queue->total = $count;
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
        $queues = $this->queueRepository->findAll();
        $result = [];
        foreach ($queues as $queue) {
            $queue->opened = $this->trackingRepository->countByQueue($queue);
            $result[] = $queue;
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
        $result = [];
        foreach ($trackings as $tracking) {
            $tracking->opened = $this->trackingRepository->countByQueueAndRecipient($tracking->getQueue(), $tracking->getRecipient());
            $result[$tracking->getRecipient()->getEmail()] = $tracking;
        }
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
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll());
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Queue $newQueue
     * @return void
     */
    public function createAction($newQueue)
    {
        $this->queueRepository->add($newQueue);
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
        $this->queueRepository->remove($queue);
        $this->persistenceManager->persistAll();
        $this->redirect('index', 'queue');
    }

    /**
     * @return void
     */
    public function startAction()
    {
        shell_exec(constant('FLOW_PATH_ROOT') . 'flow queue:process');
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
            'true',
            [],
            '',
            false,
            []
        );
        return $url;
    }

}
