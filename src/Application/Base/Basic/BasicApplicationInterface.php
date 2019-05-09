<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\Basic;

use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface BasicApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base\Basic
 */
interface BasicApplicationInterface extends ApplicationInterface
{

    public const  BASIC                  = 'basic';
    public const  AUTHORIZATION_SETTINGS = 'authorization_settings';
    public const  TOKEN                  = 'token';

    /**
     * @return string
     */
    public function getAuthorizationType(): string;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return BasicApplicationInterface
     */
    public function setAuthorizationSettings
    (
        ApplicationInstall $applicationInstall,
        array $settings
    ): BasicApplicationInterface;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $password
     *
     * @return BasicApplicationInterface
     */
    public function setApplicationPassword
    (
        ApplicationInstall $applicationInstall,
        string $password
    ): BasicApplicationInterface;

}