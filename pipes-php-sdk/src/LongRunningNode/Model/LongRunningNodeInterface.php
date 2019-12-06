<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model;

use Bunny\Message;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;

/**
 * Interface LongRunningNodeInterface
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model
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
     * @param mixed[]             $requestData
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, array $requestData): ProcessDto;

    /**
     * @param ApplicationInterface $application
     *
     * @return LongRunningNodeInterface
     */
    public function setApplication(ApplicationInterface $application): LongRunningNodeInterface;

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string;

}