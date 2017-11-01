<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Requester\Traits;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;

/**
 * Trait CMEventRequesterTrait
 *
 * @package CleverConnectors\AppBundle\Model\Requester\Traits
 */
trait CMEventRequesterTrait
{

    /**
     * @param array $data
     *
     * @return CMEventObject
     * @throws CleverConnectorsException
     */
    protected function getObject(array $data): CMEventObject
    {
        if (empty($data[RequesterInterface::OBJECT] ?? '')) {
            throw new CleverConnectorsException(
                'Missing CMEventObject in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $data[RequesterInterface::OBJECT];
    }

}
