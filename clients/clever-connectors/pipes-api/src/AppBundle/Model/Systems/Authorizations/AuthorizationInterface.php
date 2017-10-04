<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Authorizations;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;

/**
 * Interface AuthorizationInterface
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Authorizations
 */
interface AuthorizationInterface extends SystemInterface
{

    /**
     * @return bool
     */
    public function isAuthorized(): bool;

    /**
     * @return string
     */
    public function getAuthorizationType(): string;

    /**
     * @return array
     */
    public function getSettingFields(): array;

    /**
     * @return array
     */
    public function getSettings(): array;

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall;

}