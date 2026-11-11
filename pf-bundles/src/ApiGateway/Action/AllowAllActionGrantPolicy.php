<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Action;

/**
 * Class AllowAllActionGrantPolicy
 *
 * Community edition has no groups / RBAC and no audit log: any caller holding an `actions:run`
 * token may run any Action, nothing is audited.
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Action
 */
final class AllowAllActionGrantPolicy implements ActionGrantPolicyInterface
{

    /**
     * @param mixed[] $action
     * @param string  $identity
     *
     * @return ActionGrant
     */
    public function decide(array $action, string $identity): ActionGrant
    {
        unset($action, $identity);

        return new ActionGrant(TRUE);
    }

}
