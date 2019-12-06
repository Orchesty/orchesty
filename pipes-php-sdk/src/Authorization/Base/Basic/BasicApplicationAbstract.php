<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\Basic;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

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
        return
            isset(
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD]
            )
            &&
            isset(
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER]
            );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $password
     *
     * @return ApplicationInstall
     */
    public function setApplicationPassword(ApplicationInstall $applicationInstall, string $password): ApplicationInstall
    {
        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD] = $password;

        return $applicationInstall->setSettings($settings);

    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $user
     *
     * @return ApplicationInstall
     */
    public function setApplicationUser(ApplicationInstall $applicationInstall, string $user): ApplicationInstall
    {
        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER] = $user;

        return $applicationInstall->setSettings($settings);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $settings
     *
     * @return ApplicationInstall
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $applicationInstall = parent::setApplicationSettings($applicationInstall, $settings);

        foreach ($applicationInstall->getSettings()[ApplicationAbstract::FORM] ?? [] as $key => $value) {

            if ($key === BasicApplicationInterface::USER) {
                $this->setApplicationUser($applicationInstall, $value);
                continue;
            }
            if ($key === BasicApplicationInterface::PASSWORD) {
                $this->setApplicationPassword($applicationInstall, $value);
                continue;
            }
        }

        return $applicationInstall;
    }

}
