<?php
namespace NeosRulez\DirectMail\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class RecipientListFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\DirectMail\Domain\Repository\RecipientListRepository
     */
    protected $recipientListRepository;

    /**
     * @return void
     */
    public function evaluate() {
        $result = false;
        $recipientLists = $this->recipientListRepository->findAll();
        if($recipientLists) {
            $result = $recipientLists;
        }
        return $result;
    }

}