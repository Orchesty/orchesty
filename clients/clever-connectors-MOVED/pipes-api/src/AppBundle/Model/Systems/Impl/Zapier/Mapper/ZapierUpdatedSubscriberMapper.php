<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZapierUpdatedSubscriberMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierUpdatedSubscriberMapper extends ZapierSubscriberMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::UPDATE;

        return parent::process($dto);
    }

}