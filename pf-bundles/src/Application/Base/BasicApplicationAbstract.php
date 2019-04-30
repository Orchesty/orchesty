<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Class BasicApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
abstract class BasicApplicationAbstract extends ApplicationAbstract implements BasicApplicationInterface
{

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return BasicApplicationInterface::BASIC;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return isset($applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::TOKEN]);
    }

}