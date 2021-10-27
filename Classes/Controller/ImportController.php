<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

use Neos\Flow\ResourceManagement\ResourceManager;

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
     * @return void
     */
    public function indexAction()
    {
        if($this->request->hasArgument('recipientList')) {
            $this->view->assign('selectedRecipientList', $this->request->getArgument('recipientList'));
        }
        $recipientLists = $this->recipientListRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
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

    }

    /**
     * @param array $importMapping
     * @return void
     */
    public function importAction(array $importMapping)
    {

        $csv = $this->parseCsv($importMapping['fileUri']);

//        \Neos\Flow\var_dump($importMapping);

        if(!empty($csv)) {
            foreach ($csv as $recipientItem) {

                $firstname = array_key_exists('firstname', $importMapping) ? $recipientItem[$importMapping['firstname']] : '';
                $lastname = array_key_exists('lastname', $importMapping) ? $recipientItem[$importMapping['lastname']] : '';
                $email = array_key_exists('email', $importMapping) ? $recipientItem[$importMapping['email']] : false;
                $gender = array_key_exists('gender', $importMapping) ? ($importMapping['gender'] == '' ? 3 : $recipientItem[$importMapping['gender']]) : 3;
                $customsalutation = array_key_exists('customsalutation', $importMapping) ? $recipientItem[$importMapping['customsalutation']] : '';
                $recipientList = [$this->recipientListRepository->findByIdentifier($importMapping['recipientList'])];

                if($email) {
                    $email = str_replace(' ', '', $email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $newRecipient = new \NeosRulez\DirectMail\Domain\Model\Recipient();
                        $newRecipient->setFirstname($firstname);
                        $newRecipient->setLastname($lastname);
                        $newRecipient->setEmail($email);
                        $newRecipient->setGender((int) $gender);
                        $newRecipient->setCustomsalutation($customsalutation);
                        $newRecipient->setActive(true);
                        $newRecipient->setRecipientlist($recipientList);
                        $this->recipientRepository->add($newRecipient);
                    }
                }

            }
        }

        $this->redirect('edit','recipientList',Null,array('recipientList' => $importMapping['recipientList']));

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
