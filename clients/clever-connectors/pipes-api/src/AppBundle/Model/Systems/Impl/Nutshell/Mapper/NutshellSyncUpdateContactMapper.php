<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class NutshellSyncUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
class NutshellSyncUpdateContactMapper extends NutshellContactMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::UPDATE;

        return parent::processSync($dto);
    }

}