<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class WebhookRepository
 *
 * @package         Hanaboso\HbPFAppStore\Repository
 * @phpstan-extends DocumentRepository<\Hanaboso\HbPFAppStore\Document\Webhook>
 */
class WebhookRepository extends DocumentRepository
{

}