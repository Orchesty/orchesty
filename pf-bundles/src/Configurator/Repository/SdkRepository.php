<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;

/**
 * Class SdkRepository
 *
 * @package         Hanaboso\PipesFramework\Configurator\Repository
 *
 * @phpstan-extends DocumentRepository<Sdk>
 */
final class SdkRepository extends DocumentRepository
{

}
