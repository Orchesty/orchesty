<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\HbPFAppStore\Document\Webhook;

/**
 * Class WebhookRepository
 *
 * @package         Hanaboso\HbPFAppStore\Repository
 *
 * @phpstan-extends DocumentRepository<Webhook>
 */
final class WebhookRepository extends DocumentRepository
{

}
