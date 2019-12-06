<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class NotificationSettingsRepository
 *
 * @package         Hanaboso\NotificationSender\Repository
 * @phpstan-extends DocumentRepository<\Hanaboso\NotificationSender\Document\NotificationSettings>
 */
class NotificationSettingsRepository extends DocumentRepository
{

}