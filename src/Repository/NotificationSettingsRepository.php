<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\NotificationSender\Document\NotificationSettings;

/**
 * Class NotificationSettingsRepository
 *
 * @package         Hanaboso\NotificationSender\Repository
 * @phpstan-extends DocumentRepository<NotificationSettings>
 */
final class NotificationSettingsRepository extends DocumentRepository
{

}
