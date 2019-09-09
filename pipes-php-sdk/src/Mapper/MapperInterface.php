<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper;

use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;

/**
 * Interface MapperInterface
 *
 * @package Hanaboso\PipesPhpSdk\Mapper
 */
interface MapperInterface
{

    /**
     * @param array $data
     *
     * @return array
     */
    public function process(array $data): array;

    /**
     * @param ApplicationInterface $application
     *
     * @return MapperInterface
     */
    public function setApplication(ApplicationInterface $application): MapperInterface;

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string;

}