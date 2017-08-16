<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 17:22
 */

namespace Hanaboso\PipesFramework\Commons\Authorization\UserAction;

/**
 * Interface UserActionAuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\UserAction
 */
interface UserActionAuthorizationInterface
{

    /**
     * @return UserActionAuthObject[]
     */
    public function getUserActions(): array;

}