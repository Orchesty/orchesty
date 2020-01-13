<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

/**
 * Interface MapperInterface
 *
 * @package Hanaboso\PipesPhpSdk\Mapper
 */
interface MapperInterface
{

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
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
