<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use NeosRulez\DirectMail\Domain\Model\Queue;
use NeosRulez\DirectMail\Domain\Model\Recipient;

interface SlotInterface
{

    /**
     * @param mixed $argument
     * @return bool
     */
    public function execute(mixed $argument): bool;

}
