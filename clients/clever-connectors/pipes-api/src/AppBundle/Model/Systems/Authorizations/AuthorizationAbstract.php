<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Authorizations;

use CleverConnectors\AppBundle\Document\SystemInstall;

/**
 * Class AuthorizationAbstract
 *
 * @package AppBundle\Model\Systems\Authorizations
 */
abstract class AuthorizationAbstract implements AuthorizationInterface
{

    /**
     * @return array
     */
    public function getSettings(): array
    {
        // TODO: Don't forget implement :)
        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall
    {
        return $systemInstall->setSettings($data);
    }

}