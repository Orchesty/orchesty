<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;

/**
 * Class ApiTokenRepository
 *
 * @package Hanaboso\PipesFramework\Configurator\Repository
 *
 * @phpstan-extends DocumentRepository<ApiToken>
 */
final class ApiTokenRepository extends DocumentRepository
{

}
