<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract as BaseLongRunningNodeAbstract;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeInterface;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class LongRunningNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl
 */
abstract class LongRunningNodeAbstract extends BaseLongRunningNodeAbstract implements LongRunningNodeInterface
{

    /**
     * @param AMQPMessage $message
     *
     * @return LongRunningNodeData
     * @throws Exception
     */
    public function beforeAction(AMQPMessage $message): LongRunningNodeData
    {
        return LongRunningNodeData::fromMessage($message);
    }

    /**
     * @param LongRunningNodeData $data
     * @param mixed[]             $requestData
     *
     * @return ProcessDto
     * @throws JsonException
     */
    public function afterAction(LongRunningNodeData $data, array $requestData): ProcessDto
    {
        $requestData;

        return $data->toProcessDto();
    }

}
