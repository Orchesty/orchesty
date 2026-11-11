<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Action;

/**
 * Interface ActionGrantPolicyInterface
 *
 * Decides whether an identity (Orchesty user e-mail) may run an Action.
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Action
 */
interface ActionGrantPolicyInterface
{

    /**
     * @param mixed[] $action   Catalog item (key, name, app, sdk, kind, ...)
     * @param string  $identity
     *
     * @return ActionGrant
     */
    public function decide(array $action, string $identity): ActionGrant;

}
