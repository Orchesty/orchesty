<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZapierCreatedSubscriberMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierCreatedSubscriberMapper extends ZapierSubscriberMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::CREATE;

        return parent::process($dto);
    }

}