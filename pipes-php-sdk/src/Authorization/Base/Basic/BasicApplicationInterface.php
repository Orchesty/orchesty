<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\Basic;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Interface BasicApplicationInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\Basic
 */
interface BasicApplicationInterface extends ApplicationInterface
{

    public const  USER     = 'user';
    public const  PASSWORD = 'password';

    /**
     * @return string
     */
    public function getAuthorizationType(): string;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $password
     *
     * @return ApplicationInstall
     */
    public function setApplicationPassword(
        ApplicationInstall $applicationInstall,
        string $password
    ): ApplicationInstall;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $user
     *
     * @return ApplicationInstall
     */
    public function setApplicationUser(
        ApplicationInstall $applicationInstall,
        string $user
    ): ApplicationInstall;

}