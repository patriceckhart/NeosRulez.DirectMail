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

        $file = $newImport->getFile();
        $fileUri = $this->resourceManager->getPublicPersistentResourceUri($file);

        $recipients = file($fileUri);
        $recipientList = $newImport->getRecipientlist();

        foreach ($recipients as $recipient) {

            list($firstname, $lastname, $email, $gender, $customsalutation) = explode(';', $recipient);

            $newRecipient = new \NeosRulez\DirectMail\Domain\Model\Recipient();
            $newRecipient->setFirstname($firstname);
            $newRecipient->setLastname($lastname);
            $newRecipient->setEmail($email);
            $newRecipient->setGender((int) $gender);
            $newRecipient->setCustomsalutation($customsalutation);
            $newRecipient->setActive(true);
            $newRecipient->setRecipientlist([$recipientList]);

            $existingRecipient = $this->recipientRepository->findOneRecipientByMail($newRecipient->getEmail());
            if($existingRecipient) {
                $existingRecipient->setFirstname($firstname);
                $existingRecipient->setLastname($lastname);
                $existingRecipient->setEmail($email);
                $existingRecipient->setGender((int) $gender);
                $existingRecipient->setCustomsalutation($customsalutation);

                $recipientLists = $existingRecipient->getRecipientlist();
                $rawRecipientLists = [];
                foreach ($recipientLists as $list) {
                    $rawRecipientLists[$this->persistenceManager->getIdentifierByObject($list)] = $list;
                }
                $rawRecipientLists[$this->persistenceManager->getIdentifierByObject($recipientList)] = $recipientList;

                $existingRecipient->setRecipientlist($rawRecipientLists);
                $this->recipientRepository->update($existingRecipient);
            } else {
                $this->recipientRepository->add($newRecipient);
            }
        }

        $this->redirect('edit','recipientList',Null,array('recipientList' => $recipientList));

    }

}
