<?php

namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Generator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Fusion\View\FusionView;

use NeosRulez\DirectMail\Domain\Model\Recipient;
use NeosRulez\DirectMail\Domain\Model\RecipientList;

class ImportController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\ImportRepository
     */
    protected $importRepository;

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
     * @var \Neos\Flow\ResourceManagement\ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Service\MergeService
     */
    protected $mergeService;

    /**
     * @Flow\Inject
     * @var \Neos\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="contentDimensions")
     * @var array
     */
    protected $contentDimensions;


    /**
     * @return void
     */
    public function indexAction()
    {
        if ($this->request->hasArgument('recipientList')) {
            $this->view->assign('selectedRecipientList', $this->request->getArgument('recipientList'));
        }
        $this->recipientListRepository->setDefaultOrderings([
            'name' => QueryInterface::ORDER_ASCENDING,
        ]);
        $recipientLists = $this->recipientListRepository->findAll();
        $result = [];
        foreach ($recipientLists as $recipientList) {
            $recipientList->identifier = $this->persistenceManager->getIdentifierByObject($recipientList);
            $result[] = $recipientList;
        }

        $this->view->assign('recipientLists', $result);
    }

    /**
     * @param \NeosRulez\DirectMail\Domain\Model\Import $newImport
     * @return void
     */
    public function createAction($newImport)
    {
        $this->importRepository->add($newImport);
        $this->persistenceManager->persistAll();

        $file = $newImport->getFile();
        $fileUri = $this->resourceManager->getPublicPersistentResourceUri($file);

        $recipientList = $newImport->getRecipientlist();

        $csvHeaders = $this->parseCsv($fileUri)->current();
        $keys = [];

        if (!empty($csvHeaders)) {
            foreach ($csvHeaders as $i => $csvData) {
                $keys[] = $i;
            }
        }

        $this->view->assign('keys', $keys);
        $this->view->assign('fileUri', $fileUri);
        $this->view->assign('recipientList', $recipientList->getIdentifier());

        $this->view->assign('contentDimensions', $this->contentDimensions);

        if (isset($this->settings['recipient']['customFields'])) {
            $customFields = $this->settings['recipient']['customFields'];
            if (!empty($customFields)) {
                $this->view->assign('customFields', $customFields);
            }
        }
    }

    /**
     * @return void
     */
    public function createdAction() {}

    /**
     * @param array $importMapping
     * @return void
     */
    public function importAction(array $importMapping)
    {
        $imported = [];
        $notImported = [];
        $updated = [];

        foreach ($this->parseCsv($importMapping['fileUri']) as $recipientItem) {
            $email = array_key_exists('email', $importMapping) ? $recipientItem[$importMapping['email']] : false;

            if (!$email) {
                // Skip early if there is no email.
                continue;
            }

            $email = str_replace(' ', '', $email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Skip invalid email addresses.
                continue;
            }

            $firstname = array_key_exists('firstname', $importMapping) ? $recipientItem[$importMapping['firstname']] : '';
            $lastname = array_key_exists('lastname', $importMapping) ? $recipientItem[$importMapping['lastname']] : '';
            $gender = array_key_exists('gender', $importMapping) ? ($importMapping['gender'] === '' ? 3 : $recipientItem[$importMapping['gender']]) : 3;
            $customsalutation = array_key_exists('customSalutation', $importMapping) ? (array_key_exists($importMapping['customSalutation'], $recipientItem) ? $recipientItem[$importMapping['customSalutation']] : '') : '';
            $recipientList = [$this->recipientListRepository->findByIdentifier($importMapping['recipientList'])];
            $dimensions = ($importMapping['dimensions'] ?? '') !== '' ? $recipientItem[$importMapping['dimensions']] : false;
            $hasCustomFields = array_key_exists('customFields', $importMapping);

            $customFields = [];

            if ($hasCustomFields) {
                foreach ($importMapping['customFields'] as $customFieldIterator => $customField) {
                    if (!empty($customFieldIterator)) {
                        $customFields[$customFieldIterator] = $recipientItem[$customField];
                    }
                }
            }

            $existingRecipients = $this->recipientRepository->findByEmail($email);

            if ($existingRecipients->count() === 0) {
                $newRecipient = new Recipient();
                $newRecipient->setFirstname($firstname);
                $newRecipient->setLastname($lastname);
                $newRecipient->setEmail($email);
                $newRecipient->setGender((int) $gender);
                $newRecipient->setCustomsalutation($customsalutation);
                $newRecipient->setActive(true);
                if ($dimensions) {
                    $newRecipient->setDimensions($this->mapDimensions($dimensions));
                }
                if ($hasCustomFields) {
                    $newRecipient->setCustomFields($customFields);
                }
                $newRecipient->setRecipientlist($recipientList);
                $this->recipientRepository->add($newRecipient);
                $this->persistenceManager->persistAll();
                $imported[] = $email;
            } else {
                $existingRecipient = $existingRecipients->getFirst();
                $existingRecipientLists = $existingRecipient->getRecipientlist();
                if ($existingRecipientLists->contains($recipientList[0])) {
                    $notImported[] = $email;
                } else {
                    $updated[] = $email;
                    $newRecipientLists = new ArrayCollection();
                    /** @var RecipientList $existingRecipientList */
                    foreach ($existingRecipientLists as $existingRecipientList) {
                        $newRecipientLists->add($existingRecipientList);
                    }
                    $newRecipientLists->add($recipientList[0]);
                    $existingRecipient->setRecipientlist($newRecipientLists);
                    $this->recipientRepository->update($existingRecipient);
                    $this->persistenceManager->persistAll();
                }
            }
        }


        // $this->redirect('edit', 'recipientList', null, ['recipientList' => $importMapping['recipientList']]);
        $this->view->assign('imported', $imported);
        $this->view->assign('notImported', $notImported);
        $this->view->assign('updated', $updated);
        $this->view->assign('recipientList', $this->recipientListRepository->findByIdentifier($importMapping['recipientList']));
    }

    /**
     * @param string $dimensions
     * @return array
     */
    private function mapDimensions(string $dimensions): array
    {
        $dimensions = explode('_', $dimensions);
        $i = 0;
        $contentDimensions = $this->contentDimensions;
        $result = [];
        if (!empty($contentDimensions)) {
            foreach ($contentDimensions as $contentDimensionIterator => $contentDimension) {
                $result[$contentDimensionIterator] = $dimensions[$i];
                $i = $i + 1;
            }
        }
        return $result;
    }

    /**
     * @param string $file
     * @return Generator
     */
    public function parseCsv(string $file): Generator
    {
        $fh = fopen($file, 'r');
        $header = fgetcsv($fh, null, ';');

        if ($header === false) {
            return;
        }

        $headerCount = count($header);
        while (($row = fgetcsv($fh, null, ';')) !== false) {
            $row = array_pad($row, $headerCount, "");
            yield array_combine($header, $row);
        }
    }
}
