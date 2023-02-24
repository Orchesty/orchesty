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
        return AuthorizationTypeEnum::BASIC->value;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return
            (
                isset(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
                )
                &&
                isset(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
                )
            ) ||
            isset(
                $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN],
            );
    }

}
