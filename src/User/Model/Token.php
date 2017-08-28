<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class Token extends UsernamePasswordToken
{

    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        // TODO: Implement getCredentials() method.
    }
}