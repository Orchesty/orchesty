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