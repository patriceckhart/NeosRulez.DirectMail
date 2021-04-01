<?php
namespace NeosRulez\DirectMail\Controller;

/*
 * This file is part of the NeosRulez.DirectMail package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Fusion\View\FusionView;

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
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('recipientLists', $this->recipientListRepository->findAll());
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
            $this->recipientRepository->add($newRecipient);

        }

        $this->redirect('index', 'import');
    }

}
