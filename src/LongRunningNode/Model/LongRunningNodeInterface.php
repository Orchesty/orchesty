<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Bunny\Message;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;

/**
 * Interface LongRunningNodeInterface
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Model
 */
interface LongRunningNodeInterface
{

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @param Message $message
     *
     * @return LongRunningNodeData
     */
    public function beforeAction(Message $message): LongRunningNodeData;

    /**
     * @param LongRunningNodeData $data
     * @param array               $requestData
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, array $requestData): ProcessDto;

}