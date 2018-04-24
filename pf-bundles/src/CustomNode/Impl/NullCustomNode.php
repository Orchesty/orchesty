<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class NullCustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class NullCustomNode implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE), 'Null worker resending data.');

        return $dto;
    }

}