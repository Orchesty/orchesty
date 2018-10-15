<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.10.17
 * Time: 10:48
 */

namespace App\Model;

use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;

/**
 * Class NullAuthenticator
 *
 * @package App\Model
 */
class NullAuthenticator implements IAuthenticator
{

    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     *
     * @param array $credentials
     *
     * @return IIdentity
     */
    function authenticate(array $credentials)
    {
        [$userId] = $credentials;

        return new Identity($userId, NULL);
    }

}