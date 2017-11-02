<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

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
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        return parent::process($this->getNeededAction($dto, self::DELETE));
    }

}