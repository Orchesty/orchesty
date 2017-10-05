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

    public const BASIC    = 'basic';
    public const OAUTH    = 'oauth';
    public const OAUTH2   = 'oauth2';

    public const PASSWORD = 'password';

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool;

    /**
     * @return string
     */
    public function getAuthorizationType(): string;

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall;

    /**
     * @param SystemInstall $systemInstall
     * @param string        $password
     *
     * @return SystemInstall
     */
    public function setPassword(SystemInstall $systemInstall, string $password): SystemInstall;

}