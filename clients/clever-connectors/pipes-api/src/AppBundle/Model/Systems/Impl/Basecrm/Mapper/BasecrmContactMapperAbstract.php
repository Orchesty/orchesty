<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class BasecrmContactMapperAbstact
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
abstract class BasecrmContactMapperAbstract implements CustomNodeInterface
{

    /**
     * @var array
     */
    protected static $event_types = [];

    /**
     * @param array      $item
     * @param ProcessDto $dto
     *
     * @return bool
     */
    protected function checkEventType(array $item, ProcessDto $dto): bool
    {
        if (!in_array($item['meta']['sync']['event_type'], static::$event_types)) {
            $headers = [
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 1003,
                PipesHeaders::createKey(PipesHeaders::RESULT_STATUS)  => 'DO_NOT_CONTINUE',
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => 'Event type does not match mapper type, BaseCRM.',
                PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => '',
            ];

            $dto->setHeaders(array_merge($dto->getHeaders(), $headers));

            return FALSE;
        }

        return TRUE;
    }

}