<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;

/**
 * Trait AuthorizationTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits
 */
trait AuthorizationTrait
{

    /**
     * @var array
     */
    protected $topologyNames = [];

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $data = array_merge($systemInstall->getSettings(), $data);

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
     * @param string $name
     *
     * @return string
     */
    public function getCustomTopologyName(string $name): string
    {
        if (array_key_exists($name, $this->topologyNames)) {
            return $this->topologyNames[$name];
        }

        return $name;
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
        $arr = [
            'key'         => $this->getKey(),
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'type'        => $this->getType(),
            'authType'    => $this->getAuthorizationType(),
        ];

        if ($systemInstall) {
            $arr['authorized']   = $this->isAuthorized($systemInstall);
            $arr['token']        = $systemInstall->getToken();
            $arr['synchronized'] = $systemInstall->isSynchronized();
        }

        return $arr;
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
            return $settings[$key];
        }

        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @throws SystemException
     */
    protected function continueOnAuthorized(SystemInstall $systemInstall): void
    {
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException(
                sprintf('%s is not Authorized!', $this->getName()),
                SystemException::SYSTEM_IS_UNAUTHORIZED
            );
        }
    }

}