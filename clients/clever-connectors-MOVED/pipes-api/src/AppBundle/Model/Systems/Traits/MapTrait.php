<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Traits;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;

/**
 * Trait MapTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Traits
 */
trait MapTrait
{

    /**
     * @param SystemInterface $system
     *
     * @return SystemInterface
     * @throws CleverConnectorsException
     */
    protected function checkDynamicMapping(SystemInterface $system): SystemInterface
    {
        if (!$system->isDynamicMapper()) {
            throw new CleverConnectorsException(
                sprintf('System "%s" does not support dynamic mapping', $system->getKey()),
                CleverConnectorsException::DYNAMIC_MAPPING_NOT_ALLOWED
            );
        }

        return $system;
    }

    /**
     * @param SystemInterface $system
     * @param array           $data
     *
     * @return ActionDto
     * @throws CleverConnectorsException
     */
    protected function checkAction(SystemInterface $system, array $data): ActionDto
    {
        if (!isset($system->getAllowedActions()[$data['action']])) {
            throw new CleverConnectorsException(
                sprintf('System "%s" does not support action "%s"', $system->getKey(), $data['action']),
                CleverConnectorsException::ACTION_NOT_ALLOWED
            );
        }

        /** @var ActionDto $actionDto */
        $actionDto = $system->getAllowedActions()[$data['action']];

        return $actionDto;
    }

}