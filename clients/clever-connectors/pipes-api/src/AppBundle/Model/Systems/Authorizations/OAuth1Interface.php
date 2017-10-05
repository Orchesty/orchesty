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

    public const FRONTEND_REDIRECT_URL = 'frontend_redirect_url';

    /**
     * @param SystemInstall $systemInstall
     */
    public function authorize(SystemInstall $systemInstall): void;

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall;

}