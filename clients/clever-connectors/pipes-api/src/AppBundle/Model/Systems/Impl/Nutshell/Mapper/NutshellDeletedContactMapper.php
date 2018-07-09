<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class NutshellDeletedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
class NutshellDeletedContactMapper extends NutshellContactMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::DELETE;

        return parent::process($this->getNeededAction($dto, self::DELETE));
    }

}