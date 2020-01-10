<?php declare(strict_types=1);

namespace Tests\Unit\HbPFLongRunningNodeBundle\Loader;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class TestLongRunningNode
 *
 * @package Tests\Unit\HbPFLongRunningNodeBundle\Loader
 */
final class TestLongRunningNode extends LongRunningNodeAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return '';
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

        return new ProcessDto();
    }

}