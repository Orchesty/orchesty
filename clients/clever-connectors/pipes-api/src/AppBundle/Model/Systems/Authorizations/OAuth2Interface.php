<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Authorizations;

use CleverConnectors\AppBundle\Document\SystemInstall;

/**
 * Interface OAuth2Interface
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Authorizations
 */
interface OAuth2Interface extends OAuth1Interface
{

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function refreshToken(SystemInstall $systemInstall, array $data): SystemInstall;

}