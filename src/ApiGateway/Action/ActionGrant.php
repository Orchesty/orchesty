<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Action;

/**
 * Class ActionGrant
 *
 * Authorization decision for one run of an Action by an identity. The worker keeps it for
 * ActionManager::GRANT_TTL seconds and loads the ApplicationInstall itself.
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Action
 */
final readonly class ActionGrant
{

    /**
     * ActionGrant constructor.
     *
     * @param bool   $allowed
     * @param bool   $audit   Worker must record the run (write intent before the call, fail-closed)
     * @param string $reason
     */
    public function __construct(public bool $allowed, public bool $audit = FALSE, public string $reason = '')
    {
    }

}
