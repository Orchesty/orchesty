<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ZendeskUpdatedUserMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
class ZendeskUpdatedUserMapper extends ZendeskUserMapperAbstract
{

    /**
     * @param ProcessDto $dto
     * @param array      $data
     *
     * @return bool
     */
    protected function omit(ProcessDto $dto, array $data): bool
    {
        return $data['created_at'] === $data['updated_at'];
    }

}