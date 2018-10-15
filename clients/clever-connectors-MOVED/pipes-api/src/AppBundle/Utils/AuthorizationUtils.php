<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Traits\StaticTrait;

/**
 * Class AuthorizationUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class AuthorizationUtils
{

    use StaticTrait;

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return string
     */
    public static function generateUrl(?SystemInstall $systemInstall = NULL): string
    {
        if ($systemInstall) {
            return sprintf('/user_systems/user/%s/system/%s/authorize_redirect/%s',
                $systemInstall->getUser(),
                $systemInstall->getSystem(),
                $systemInstall->getSettings()[OAuth1Interface::FRONTEND_REDIRECT_URL]
            );
        } else {
            return '/user_systems/saveToken';
        }
    }

}