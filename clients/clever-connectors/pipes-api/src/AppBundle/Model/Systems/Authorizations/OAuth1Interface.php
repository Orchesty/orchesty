<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Authorizations;

use CleverConnectors\AppBundle\Document\SystemInstall;

/**
 * Interface OAuth1Interface
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Authorizations
 */
interface OAuth1Interface extends AuthorizationInterface
{

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     */
    public function authorize(SystemInstall $systemInstall, array $data): void;

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall;

}