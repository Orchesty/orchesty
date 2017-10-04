<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:35 PM
 */

namespace CcApi\Connector;

use CcApi\ApiEntity\System;
use CcApi\ApiEntity\UserSystem;

/**
 * Interface ConnectorInterface
 *
 * @package CcApi
 */
interface ConnectorInterface
{

    /**
     * @param null|string $group
     * @param null|string $user
     *
     * @return iterable|System[]
     */
    public function getAllSystems(?string $group = NULL, ?string $user = NULL): iterable;

    /**
     * @param string $systemKey
     *
     * @return System
     */
    public function getSystem(string $systemKey): System;

    /**
     * @param string $userId
     * @param string $systemKey
     *
     * @return UserSystem
     */
    public function getUserSystem(string $userId, string $systemKey): UserSystem;

}