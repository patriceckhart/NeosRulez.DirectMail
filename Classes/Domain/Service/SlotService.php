<?php
namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class SlotService
{

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.DirectMail", path="slots.addRecipientToQueueSlots")
     * @var array
     */
    protected $addRecipientToQueueSlots;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param mixed $arguments
     * @return bool
     */
    public function addRecipientToQueue(mixed $arguments): bool
    {
        foreach ($this->addRecipientToQueueSlots as $addRecipientToQueueSlot) {
            $slotClass = $this->objectManager->get($addRecipientToQueueSlot['class']);
            if($slotClass->execute($arguments) === false) {
                return false;
            }
        }
        return true;
    }

}
