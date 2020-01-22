<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFMapperBundle;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Mapper\MapperInterface;

/**
 * Class NullMapper
 *
 * @package PipesPhpSdkTests\Integration\HbPFMapperBundle
 */
final class NullMapper implements MapperInterface
{

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function process(array $data): array
    {
        return $data;
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return MapperInterface
     */
    public function setApplication(ApplicationInterface $application): MapperInterface
    {
        $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        return 'key';
    }

}
