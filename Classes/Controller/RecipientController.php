<?php

namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Fusion\View\FusionView;
use NeosRulez\DirectMail\Domain\Model\Recipient;
use NeosRulez\DirectMail\Domain\Model\RecipientList;

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
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="contentDimensions")
     * @var array
     */
    protected $contentDimensions;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param integer $offset
     * @param integer $length
     * @param integer $itemsPerLoad
     * @param integer $page
     * @param string $searchstring
     * @param boolean $filterInactive
     * @return void
     */
    public function indexAction(
        int $offset = 0,
        int $length = 50,
        int $itemsPerLoad = 50,
        int $page = 1,
        string $searchstring = '',
        bool $filterInactive = false,
    ) {
        $this->recipientRepository->setDefaultOrderings([
            'created' => QueryInterface::ORDER_DESCENDING,
            'email' => QueryInterface::ORDER_ASCENDING,
        ]);
        if ($filterInactive) {
            $recipients = $this->recipientRepository->findInactiveRecipients();
            $this->view->assign('hideFilter', true);
        } else if ($searchstring !== '') {
            $recipients = $this->recipientRepository->findBySearchstring($searchstring);
        } else {
            $recipients = $this->recipientRepository->findAll();
        }

        $combinedRecipients = [];
        if ($recipients) {
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
            if ($pages > 1) {
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
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function newAction(?RecipientList $selectedRecipientList = null)
    {
        $this->recipientListRepository->setDefaultOrderings([
            'name' => QueryInterface::ORDER_ASCENDING,
        ]);
        if ($selectedRecipientList !== null) {
            $this->view->assign('selectedRecipientList', $this->persistenceManager->getIdentifierByObject($selectedRecipientList));
        }
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll());

        $this->view->assign('contentDimensions', $this->contentDimensions);

        if (isset($this->settings['recipient']['customFields'])) {
            $customFields = $this->settings['recipient']['customFields'];
            if (!empty($customFields)) {
                $this->view->assign('customFields', $customFields);
            }
        }
    }

    /**
     * @param Recipient $newRecipient
     * @param string $selectedRecipientList
     * @return void
     */
    public function createAction(Recipient $newRecipient, string $selectedRecipientList = '')
    {
        $recipient = $this->recipientRepository->findOneRecipientByMail($newRecipient->getEmail());

        $dimensions = false;
        if ($this->request->hasArgument('dimensions')) {
            $dimensions = $this->request->getArgument('dimensions');
        }

        $customFields = false;
        if ($this->request->hasArgument('customFields')) {
            $customFields = $this->request->getArgument('customFields');
        }

        if ($recipient) {
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
            $recipient->setGender((int)$newRecipient->getGender());
            $recipient->setCustomsalutation($newRecipient->getCustomsalutation());

            if ($dimensions) {
                $recipient->setDimensions($dimensions);
            }

            if ($customFields) {
                $newRecipient->setCustomFields($customFields);
            }

            $recipient->setActive(true);
            $this->recipientRepository->update($recipient);
        } else {
            if ($customFields) {
                $newRecipient->setCustomFields($customFields);
            }
            $newRecipient->setActive(true);
            if ($dimensions) {
                $newRecipient->setDimensions($dimensions);
            }
            $this->recipientRepository->add($newRecipient);
        }
        if ($selectedRecipientList !== '') {
            $this->redirect('edit', 'recipientList', null, ['recipientList' => $selectedRecipientList]);
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param Recipient $recipient
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function editAction(Recipient $recipient, ?RecipientList $selectedRecipientList = null)
    {
        $this->recipientListRepository->setDefaultOrderings([
            'name' => QueryInterface::ORDER_ASCENDING,
        ]);
        if ($selectedRecipientList !== null) {
            $this->view->assign('selectedRecipientList', $this->persistenceManager->getIdentifierByObject($selectedRecipientList));
        }
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll());

        $this->view->assign('recipient', $recipient);

        $recipientContentDimensions = $recipient->getDimensions();
        $contentDimensions = [];
        if (!empty($this->contentDimensions) && $recipientContentDimensions !== null) {
            foreach ($this->contentDimensions as $contentDimensionIterator => $contentDimension) {
                if (array_key_exists($contentDimensionIterator, $recipientContentDimensions)) {
                    foreach ($contentDimension['presets'] as $contentDimensionPresetIterator => $contentDimensionPreset) {
                        if ($recipientContentDimensions[$contentDimensionIterator] == $contentDimensionPresetIterator) {
                            $contentDimension['presets'][$contentDimensionPresetIterator]['selected'] = true;
                        }
                    }
                }
                $contentDimensions[$contentDimensionIterator] = $contentDimension;
            }
        }

        $this->view->assign('contentDimensions', $contentDimensions);

        if (array_key_exists('recipient', $this->settings)) {
            if (array_key_exists('customFields', $this->settings['recipient'])) {
                $customFields = $this->settings['recipient']['customFields'];
                if (!empty($customFields)) {
                    $this->view->assign('customFields', $customFields);
                    $this->view->assign('customFieldsValues', $recipient->getCustomFields());
                }
            }
        }
    }

    /**
     * @param Recipient $recipient
     * @param string $selectedRecipientList
     * @return void
     */
    public function updateAction(Recipient $recipient, string $selectedRecipientList = '')
    {
        if ($this->request->hasArgument('dimensions')) {
            $dimensions = $this->request->getArgument('dimensions');
            $recipient->setDimensions($dimensions);
        }
        if ($this->request->hasArgument('customFields')) {
            $customFields = $this->request->getArgument('customFields');
            $recipient->setCustomFields($customFields);
        }
        $this->recipientRepository->update($recipient);
        if ($selectedRecipientList != '') {
            $this->redirect('edit', 'recipientList', null, ['recipientList' => $selectedRecipientList]);
        }
        $this->redirect('index', 'recipient');
    }

    /**
     * @param Recipient $recipient
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function changeAction(Recipient $recipient, ?RecipientList $selectedRecipientList = null)
    {
        if ($recipient->getActive() === true) {
            $recipient->setActive(false);
        } else {
            $recipient->setActive(true);
        }
        $this->recipientRepository->update($recipient);

        if ($selectedRecipientList !== null) {
            $this->redirect('edit', 'recipientList', null, ['recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)]);
        }
        //        $this->redirect('index', 'recipient');
        $this->redirectToUri($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param Recipient $recipient
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function deleteAction(Recipient $recipient, ?RecipientList $selectedRecipientList = null)
    {
        $trackings = $this->trackingRepository->findByRecipient($recipient);
        if (!empty($trackings)) {
            foreach ($trackings as $tracking) {
                $this->trackingRepository->remove($tracking);
                $this->persistenceManager->persistAll();
            }
        }

        $queueRecipients = $this->queueRecipientRepository->findByRecipient($recipient);
        if (!empty($queueRecipients)) {
            foreach ($queueRecipients as $queueRecipient) {
                $this->queueRecipientRepository->remove($queueRecipient);
                $this->persistenceManager->persistAll();
            }
        }

        $this->recipientRepository->remove($recipient);
        $this->persistenceManager->persistAll();

        if ($selectedRecipientList !== null) {
            $this->redirect('edit', 'recipientList', null, ['recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)]);
        }
        //        $this->redirect('index', 'recipient');
        $this->redirectToUri($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param array $recipients
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function activateSelectedAction(array $recipients, ?RecipientList $selectedRecipientList = null)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            if ($recipientObject !== null) {
                $recipientObject->setActive(true);
                $this->recipientRepository->update($recipientObject);
            }
        }

        if ($selectedRecipientList !== null) {
            $this->redirect(
                actionName: 'edit',
                controllerName: 'recipientList',
                arguments: ['recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)]
            );
        }
        // $this->redirect('index', 'recipient');
        $this->redirectToUri($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param array $recipients
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function deactivateSelectedAction(array $recipients, ?RecipientList $selectedRecipientList = null)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);
            $recipientObject->setActive(false);
            $this->recipientRepository->update($recipientObject);
        }

        if ($selectedRecipientList !== null) {
            $this->redirect(
                actionName: 'edit',
                controllerName: 'recipientList',
                arguments: ['recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)]
            );
        }
        // $this->redirect('index', 'recipient');
        $this->redirectToUri($_SERVER['HTTP_REFERER']);
    }

    /**
     * @param array $recipients
     * @param RecipientList $selectedRecipientList
     * @return void
     */
    public function deleteSelectedAction(array $recipients, ?RecipientList $selectedRecipientList = null)
    {
        $recipients = explode(',', $recipients['recipients']);
        foreach ($recipients as $recipient) {
            $recipientObject = $this->recipientRepository->findRecipientByIdentifier($recipient);

            $trackings = $this->trackingRepository->findByRecipient($recipientObject);
            if (!empty($trackings)) {
                foreach ($trackings as $tracking) {
                    $this->trackingRepository->remove($tracking);
                    $this->persistenceManager->persistAll();
                }
            }

            $queueRecipients = $this->queueRecipientRepository->findByRecipient($recipientObject);
            if (!empty($queueRecipients)) {
                foreach ($queueRecipients as $queueRecipient) {
                    $this->queueRecipientRepository->remove($queueRecipient);
                    $this->persistenceManager->persistAll();
                }
            }

            $this->recipientRepository->remove($recipientObject);
        }

        if ($selectedRecipientList !== null) {
            $this->redirect(
                actionName: 'edit',
                controllerName: 'recipientList',
                arguments: ['recipientList' => $this->persistenceManager->getIdentifierByObject($selectedRecipientList)]
            );
        }
        // $this->redirect('index', 'recipient');
        $this->redirectToUri($_SERVER['HTTP_REFERER']);
    }
}
