<?php declare(strict_types=1);

namespace Tests\Unit\HbPFLongRunningNodeBundle\Loader;

use Bunny\Message;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract;

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
     * @param Message $message
     *
     * @return LongRunningNodeData
     * @throws Exception
     */
    public function beforeAction(Message $message): LongRunningNodeData
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