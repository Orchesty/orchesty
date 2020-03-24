<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class SdkRepository
 *
 * @package         Hanaboso\PipesFramework\Configurator\Repository
 *
 * @phpstan-extends DocumentRepository<\Hanaboso\PipesFramework\Configurator\Document\Sdk>
 */
class SdkRepository extends DocumentRepository
{

}
