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

    /**
     * @param string $userId
     *
     * @return iterable|UserSystem[]
     */
    public function getAllUserSystems(string $userId): iterable;

    /**
     * @param string $userId
     * @param string $systemKey
     * @param array  $settings
     */
    public function saveUserSystemSetting(string $userId, string $systemKey, array $settings): void;

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $token
     */
    public function installUserSystem(string $userId, string $systemKey, string $token): void;

    /**
     * @param string $userId
     * @param string $systemKey
     */
    public function uninstallUserSystem(string $userId, string $systemKey): void;

    /**
     * @param string $userId
     * @param string $systemKey
     */
    public function synchronizeUserSystem(string $userId, string $systemKey): void;

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $password
     */
    public function setUserSystemPassword(string $userId, string $systemKey, string $password): void;

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $token
     */
    public function switchUserSystemToken(string $userId, string $systemKey, string $token): void;

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $redirectUrl
     */
    public function authorizeUserSystem(string $userId, string $systemKey, string $redirectUrl): void;

}