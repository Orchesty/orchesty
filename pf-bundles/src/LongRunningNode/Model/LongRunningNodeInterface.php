<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

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
     * @param LongRunningNodeData $data
     * @param ProcessDto          $dto
     *
     * @return ProcessDto
     */
    public function beforeAction(LongRunningNodeData $data, ProcessDto $dto): ProcessDto;

    /**
     * @param LongRunningNodeData $data
     * @param ProcessDto          $dto
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, ProcessDto $dto): ProcessDto;

}