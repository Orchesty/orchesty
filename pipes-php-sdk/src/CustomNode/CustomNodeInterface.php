<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

/**
 * Interface CustomNodeInterface
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
interface CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto;

    /**
     * @param ApplicationInterface $application
     *
     * @return CustomNodeInterface
     */
    public function setApplication(ApplicationInterface $application): CustomNodeInterface;

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string;

}