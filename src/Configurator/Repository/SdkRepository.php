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
     * @return mixed[]
     * @throws TopologyException
     */
    public function findByHost(string $host): array {
        return $this->findOneBy(['url' => $host])?->getHeaders() ??
           throw new TopologyException(
               sprintf('Selected host "%s" was not found.', $host),
               TopologyException::SDK_HEADERS_NOT_FOUND
           );
    }

}
