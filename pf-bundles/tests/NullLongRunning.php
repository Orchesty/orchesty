<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class NullLongRunning
 *
 * @package PipesFrameworkTests
 */
final class NullLongRunning extends LongRunningNodeAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'null';
    }

    /**
     * @param AMQPMessage $message
     *
     * @return LongRunningNodeData
     * @throws Exception
     */
    public function beforeAction(AMQPMessage $message): LongRunningNodeData
    {
        $message;

        return new LongRunningNodeData();
    }

    /**
     * @param LongRunningNodeData $data
     * @param mixed[]             $requestData
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, array $requestData): ProcessDto
    {
        $data;
        $requestData;

        return (new ProcessDto())->setData('{"key":"value"}');
    }

}
