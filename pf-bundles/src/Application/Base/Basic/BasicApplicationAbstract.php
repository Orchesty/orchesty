<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\Basic;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\PipesFramework\Application\Base\ApplicationAbstract;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Class BasicApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base\Basic
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