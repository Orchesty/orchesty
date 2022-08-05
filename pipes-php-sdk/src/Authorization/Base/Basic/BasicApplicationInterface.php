<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\Basic;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

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

}
