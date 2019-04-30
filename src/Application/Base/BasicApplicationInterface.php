<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface BasicApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
interface BasicApplicationInterface extends ApplicationInterface
{

    public const BASIC  = 'basic';
    public const OAUTH  = 'oauth';
    public const OAUTH2 = 'oauth2';

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