<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Provider;

use Hanaboso\UserBundle\Entity\UserInterface;

/**
 * Interface ProviderInterface
 *
 * @package Hanaboso\PipesFramework\Acl\Provider
 */
interface ProviderInterface
{

    /**
     * @param UserInterface $user
     *
     * @return array
     */
    public function getRules(UserInterface $user): array;

}