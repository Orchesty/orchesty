<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model\Impl;

use Bunny\Message;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeInterface;

/**
 * Class LongRunningNodeAbstract
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Model\Impl
 */
abstract class LongRunningNodeAbstract implements LongRunningNodeInterface
{

    /**
     * @param Message $message
     *
     * @return LongRunningNodeData
     */
    public function beforeAction(Message $message): LongRunningNodeData
    {
        return LongRunningNodeData::fromMessage($message);
    }

    /**
     * @param LongRunningNodeData $data
     * @param string              $requestData
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, string $requestData): ProcessDto
    {
        $requestData;

        return $data->toProcessDto();
    }

}
