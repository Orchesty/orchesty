<?php declare(strict_types=1);

namespace NotificationSenderTests\Integration\Model\Notification\Handler\Impl;

/**
 * Class NullUnknownHandler
 *
 * @package NotificationSenderTests\Integration\Model\Notification\Handler\Impl
 */
final class NullUnknownHandler
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Unknown Test Sender';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'Unknown';
    }

}
