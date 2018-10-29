<?php declare(strict_types=1);

namespace Tests\Unit\HbPFLongRunningNodeBundle\Loader;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeInterface;

/**
 * Class TestLongRunningNode
 *
 * @package Tests\Unit\HbPFLongRunningNodeBundle\Loader
 */
class TestLongRunningNode implements LongRunningNodeInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return '';
    }

    /**
     * @param LongRunningNodeData $data
     * @param ProcessDto          $dto
     *
     * @return ProcessDto
     */
    public function beforeAction(LongRunningNodeData $data, ProcessDto $dto): ProcessDto
    {
        $data;

        return $dto;
    }

    /**
     * @param LongRunningNodeData $data
     * @param ProcessDto          $dto
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, ProcessDto $dto): ProcessDto
    {
        $data;

        return $dto;
    }

}