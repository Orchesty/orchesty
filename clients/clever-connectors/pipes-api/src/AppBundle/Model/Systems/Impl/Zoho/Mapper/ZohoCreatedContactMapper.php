<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZohoCreatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
class ZohoCreatedContactMapper extends ZohoContactMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::CREATE;

        return parent::process($this->getNeededAction($dto));
    }

}