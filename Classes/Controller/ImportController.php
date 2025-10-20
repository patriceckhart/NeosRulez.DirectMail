<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

use Neos\Flow\ResourceManagement\ResourceManager;
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
        if($this->request->hasArgument('recipientList')) {
            $this->view->assign('selectedRecipientList', $this->request->getArgument('recipientList'));
        }
        $recipientLists = $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('name' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        $result = [];
        if($recipientLists) {
            foreach ($recipientLists as $recipientList) {
                $recipientList->identifier = $this->persistenceManager->getIdentifierByObject($recipientList);
                $result[] = $recipientList;
            }
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

        $csv = $this->parseCsv($fileUri);
        $keys = [];
        if(!empty($csv)) {
            foreach ($csv[0] as $i => $csvData) {
                $keys[] = $i;
            }
        }

        $this->view->assign('keys', $keys);
        $this->view->assign('fileUri', $fileUri);
        $this->view->assign('recipientList', $recipientList->getIdentifier());

        $this->view->assign('contentDimensions', $this->contentDimensions);

        if(array_key_exists('recipient', $this->settings)) {
            if(array_key_exists('customFields', $this->settings['recipient'])) {
                $customFields = $this->settings['recipient']['customFields'];
                if(!empty($customFields)) {
                    $this->view->assign('customFields', $customFields);
                }
            }
        }

    }

    /**
     * @return void
     */
    public function createdAction()
    {

    }

    /**
     * @param array $importMapping
     * @return void
     */
    public function importAction(array $importMapping)
    {

        $imported = [];
        $notImported = [];
        $updated = [];

        $csv = $this->parseCsv($importMapping['fileUri']);

        if(!empty($csv)) {

            foreach ($csv as $recipientItem) {

                $firstname = array_key_exists('firstname', $importMapping) ? $recipientItem[$importMapping['firstname']] : '';
                $lastname = array_key_exists('lastname', $importMapping) ? $recipientItem[$importMapping['lastname']] : '';
                $email = array_key_exists('email', $importMapping) ? $recipientItem[$importMapping['email']] : false;
                $gender = array_key_exists('gender', $importMapping) ? ($importMapping['gender'] == '' ? 3 : $recipientItem[$importMapping['gender']]) : 3;
                $customsalutation = array_key_exists('customSalutation', $importMapping) ? (array_key_exists($importMapping['customSalutation'], $recipientItem) ? $recipientItem[$importMapping['customSalutation']] : '') : '';
                $recipientList = [$this->recipientListRepository->findByIdentifier($importMapping['recipientList'])];
                $dimensions = array_key_exists('dimensions', $importMapping) ? ($importMapping['dimensions'] !== '' ? $recipientItem[$importMapping['dimensions']] : false) : false;
                $hasCustomFields = array_key_exists('customFields', $importMapping);

                $customFields = [];

                if($hasCustomFields) {
                    foreach ($importMapping['customFields'] as $customFieldIterator => $customField) {
                        if(!empty($customFieldIterator)) {
                            if($customField !== '') {
                                $customFields[$customFieldIterator] = $recipientItem[$customField];
                            }
                        }
                    }
                }

                if($email) {
                    $email = str_replace(' ', '', $email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                        $existingRecipients = $this->recipientRepository->findByEmail($email);

                        if($existingRecipients->count() === 0) {
                            $newRecipient = new \NeosRulez\DirectMail\Domain\Model\Recipient();
                            $newRecipient->setFirstname($firstname);
                            $newRecipient->setLastname($lastname);
                            $newRecipient->setEmail($email);
                            $newRecipient->setGender((int) $gender);
                            $newRecipient->setCustomsalutation($customsalutation);
                            $newRecipient->setActive(true);
                            if($dimensions) {
                                $newRecipient->setDimensions($this->mapDimensions($dimensions));
                            }
                            if($hasCustomFields) {
                                $newRecipient->setCustomFields($customFields);
                            }
                            $newRecipient->setRecipientlist($recipientList);
                            $this->recipientRepository->add($newRecipient);
                            $this->persistenceManager->persistAll();
                            $imported[] = $email;
                        } else {
                            foreach ($existingRecipients as $existingRecipient) {
                                $existingRecipientLists = $existingRecipient->getRecipientlist();
                                if ($existingRecipientLists->contains($recipientList[0])) {
                                    $notImported[] = $email;
                                    continue;
                                }

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
                }

            }
        }

//        $this->redirect('edit','recipientList',Null,array('recipientList' => $importMapping['recipientList']));
        $this->view->assign('imported', $imported);
        $this->view->assign('notImported', $notImported);
        $this->view->assign('updated', $updated);
        $this->view->assign('recipientList', $this->recipientListRepository->findByIdentifier($importMapping['recipientList']));
    }

    /**
     * @param string $dimensions
     * @return array
     */
    private function mapDimensions(string $dimensions):array
    {
        $dimensions = explode('_', $dimensions);
        $i = 0;
        $contentDimensions = $this->contentDimensions;
        $result = [];
        if(!empty($contentDimensions)) {
            foreach ($contentDimensions as $contentDimensionIterator => $contentDimension) {
                $result[$contentDimensionIterator] = $dimensions[$i];
                $i = $i + 1;
            }
        }
        return $result;
    }

    /**
     * @param string $file
     * @return array
     */
    public function parseCsv(string $file):array
    {
        $filePathProductNamesT = $file;
        $rows = array_map(function($data) { return str_getcsv($data,";");}, file($filePathProductNamesT));
        $header = array_shift($rows);
        foreach($rows as $row) {
            $row = array_pad($row, count($header), "");
            $csv[] = array_combine($header, $row);
        }
        return $csv;
    }

}
