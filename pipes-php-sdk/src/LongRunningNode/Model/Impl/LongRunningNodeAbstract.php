<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl;

use Bunny\Message;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeInterface;

/**
 * Class LongRunningNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl
 */
abstract class LongRunningNodeAbstract implements LongRunningNodeInterface
{

    /**
     * @param Message $message
     *
     * @return LongRunningNodeData
     * @throws Exception
     */
    public function beforeAction(Message $message): LongRunningNodeData
    {
        return LongRunningNodeData::fromMessage($message);
    }

    /**
     * @param LongRunningNodeData $data
     * @param array               $requestData
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, array $requestData): ProcessDto
    {
        $requestData;

        return $data->toProcessDto();
    }

}
