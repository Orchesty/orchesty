<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZohoSyncUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
class ZohoSyncUpdateContactMapper extends ZohoContactMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::CREATE;

        return parent::process($dto);
    }

}