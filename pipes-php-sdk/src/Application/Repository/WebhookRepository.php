<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;

/**
 * Class WebhookRepository
 *
 * @package         Hanaboso\PipesPhpSdk\Application\Repository
 *
 * @phpstan-extends DocumentRepository<Webhook>
 */
final class WebhookRepository extends DocumentRepository
{

}
