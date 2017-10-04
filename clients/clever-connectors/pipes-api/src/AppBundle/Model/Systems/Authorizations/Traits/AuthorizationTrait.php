<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;

/**
 * Trait AuthorizationTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits
 */
trait AuthorizationTrait
{

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $data[AuthorizationInterface::PASSWORD] = $systemInstall->getSettings()[AuthorizationInterface::PASSWORD] ?? NULL;

        return $systemInstall->setSettings($data);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $password
     *
     * @return SystemInstall
     */
    public function setPassword(SystemInstall $systemInstall, string $password): SystemInstall
    {
        $settings                                   = $systemInstall->getSettings();
        $settings[AuthorizationInterface::PASSWORD] = $password;

        return $systemInstall->setSettings($settings);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $frontendRedirectUrl
     *
     * @return SystemInstall
     */
    public function saveFrontendRedirectUrl(SystemInstall $systemInstall, string $frontendRedirectUrl): SystemInstall
    {
        $settings                                         = $systemInstall->getSettings();
        $settings[OAuth1Interface::FRONTEND_REDIRECT_URL] = $frontendRedirectUrl;

        return $systemInstall->setSettings($settings);
    }

    /**
     * @param string $key
     * @param array  $settings
     *
     * @return bool|mixed|null
     */
    protected function prepareValue(string $key, array $settings)
    {
        if (isset($settings[$key])) {
            if ($key == AuthorizationInterface::PASSWORD) {
                return empty($settings[AuthorizationInterface::PASSWORD]) ? FALSE : TRUE;
            }

            return $settings[$key];
        }

        return NULL;
    }

}