<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZohoUpdatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
class ZohoUpdatedContactMapper extends ZohoContactMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::UPDATE;

        return parent::process($this->getNeededAction($dto));
    }

}