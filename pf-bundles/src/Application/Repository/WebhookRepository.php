<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Application\Document\Webhook;

/**
 * Class WebhookRepository
 *
 * @package Hanaboso\PipesFramework\Application\Repository
 *
 * @phpstan-extends DocumentRepository<Webhook>
 */
final class WebhookRepository extends DocumentRepository
{

}
