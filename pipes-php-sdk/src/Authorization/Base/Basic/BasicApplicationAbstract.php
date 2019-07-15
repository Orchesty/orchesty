<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\Basic;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;

/**
 * Class BasicApplicationAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\Basic
 */
abstract class BasicApplicationAbstract extends ApplicationAbstract implements BasicApplicationInterface
{

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::BASIC;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD])
            && isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $password
     *
     * @return ApplicationInstall
     */
    public function setApplicationPassword(ApplicationInstall $applicationInstall, string $password): ApplicationInstall
    {
        return $applicationInstall->setSettings([ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::PASSWORD => $password]]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $user
     *
     * @return ApplicationInstall
     */
    public function setApplicationUser(ApplicationInstall $applicationInstall, string $user): ApplicationInstall
    {
        return $applicationInstall->setSettings([ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::USER => $user]]);
    }

}