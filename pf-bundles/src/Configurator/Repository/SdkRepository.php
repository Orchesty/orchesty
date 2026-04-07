<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;

/**
 * Class SdkRepository
 *
 * @package         Hanaboso\PipesFramework\Configurator\Repository
 *
 * @phpstan-extends DocumentRepository<Sdk>
 */
final class SdkRepository extends DocumentRepository
{

    /**
     * @param string $host
     *
     * @return Sdk
     * @throws TopologyException
     */
    public function findByHost(string $host): Sdk
    {
        return $this->findOneBy(['url' => $host]) ??
            throw new TopologyException(
                sprintf('Selected host "%s" was not found.', $host),
                TopologyException::SDK_HEADERS_NOT_FOUND,
            );
    }

    /**
     * @param string $name
     *
     * @return Sdk
     * @throws TopologyException
     */
    public function findByName(string $name): Sdk
    {
        return $this->findOneBy(['name' => $name]) ??
            throw new TopologyException(
                sprintf('SDK with name "%s" was not found.', $name),
                TopologyException::SDK_HEADERS_NOT_FOUND,
            );
    }

}
