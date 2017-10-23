<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Webhook\Provider;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.10.17
 * Time: 12:00
 */

use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;

/**
 * Interface WebhookProciderInterface
 *
 * @package CleverConnectors\AppBundle\Model\Webhook\Provider
 */
interface WebhookProviderInterface
{

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param bool                   $isUpdate
     */
    public function subscribe(WebhookSystemInterface $system, string $userId, string $token, $isUpdate = FALSE): void;

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     */
    public function unsubscribe(WebhookSystemInterface $system, string $userId): void;

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     */
    public function update(WebhookSystemInterface $system, string $userId, string $token): void;

}