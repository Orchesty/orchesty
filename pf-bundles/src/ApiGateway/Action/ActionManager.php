<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Action;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Throwable;

/**
 * Class ActionManager
 *
 * Control plane of Actions: the catalog pulled from SDK workers and the grant (authorization
 * decision). Payloads never pass through here; the worker calls the target system itself.
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Action
 */
final readonly class ActionManager
{

    public const int GRANT_TTL = 60;

    private const array GRANT_SCOPES = [ApiTokenScopesEnum::ACTIONS_RUN->value, ApiTokenScopesEnum::WORKER_ALL->value];

    /**
     * ActionManager constructor.
     *
     * @param ServiceLocator             $locator
     * @param DocumentManager            $dm
     * @param ActionGrantPolicyInterface $policy
     */
    public function __construct(
        private ServiceLocator $locator,
        private DocumentManager $dm,
        private ActionGrantPolicyInterface $policy,
    )
    {
    }

    /**
     * @return mixed[]
     */
    public function getCatalog(): array
    {
        return $this->locator->getActions();
    }

    /**
     * @param string $key `<application>.<name>`
     *
     * @return mixed[]
     * @throws ActionException
     */
    public function getAction(string $key): array
    {
        foreach ($this->getCatalog() as $action) {
            if (($action['key'] ?? NULL) === $key) {
                return $action;
            }
        }

        throw new ActionException(sprintf('Action "%s" not found.', $key), ActionException::NOT_FOUND);
    }

    /**
     * @param string|null $apiKey
     * @param mixed[]     $body   {action, identity}
     *
     * @return mixed[]
     * @throws ActionException
     */
    public function grant(?string $apiKey, array $body): array
    {
        $this->assertApiKey($apiKey);

        $key      = $body['action'] ?? NULL;
        $identity = $body['identity'] ?? '';
        if (!is_string($key) || $key === '' || !is_string($identity)) {
            throw new ActionException('Fields "action" and "identity" are required.', ActionException::INVALID_REQUEST);
        }

        $action   = $this->getAction($key);
        $decision = $this->policy->decide($action, $identity);
        if (!$decision->allowed) {
            throw new ActionException(
                $decision->reason !== '' ? $decision->reason : sprintf('Running action "%s" is not allowed.', $key),
                ActionException::FORBIDDEN,
            );
        }

        return [
            'action'     => $key,
            'allowed'    => TRUE,
            'audit'      => $decision->audit,
            'expires_in' => self::GRANT_TTL,
        ];
    }

    /**
     * Runs an action on its worker for an identity (e.g. an MCP tool call); the worker asks for its
     * own grant and audits the run.
     *
     * @param mixed[] $action   Catalog item
     * @param mixed[] $input
     * @param string  $identity
     *
     * @return mixed
     * @throws ActionException
     */
    public function run(array $action, array $input, string $identity): mixed
    {
        $key = is_string($action['key'] ?? NULL) ? $action['key'] : '';

        try {
            $response = $this->locator->runAction((string) ($action['sdk'] ?? ''), $key, $input, $identity);
        } catch (Throwable $e) {
            throw new ActionException(sprintf('Action "%s" failed: %s', $key, $e->getMessage()), 502, $e);
        }

        if ($response['status'] >= 300) {
            $error = $response['body']['error'] ?? NULL;

            throw new ActionException(
                is_string($error) ? $error : sprintf('Action "%s" failed (%d).', $key, $response['status']),
                $response['status'],
            );
        }

        return $response['body']['result'] ?? NULL;
    }

    /**
     * Workers authenticate with their Orchesty API key (`actions:run` or `worker:all` scope).
     *
     * @param string|null $apiKey
     *
     * @throws ActionException
     */
    public function assertApiKey(?string $apiKey): void
    {
        $token = $apiKey ? $this->dm->getRepository(ApiToken::class)->findOneBy([ApiToken::KEY => $apiKey]) : NULL;

        if (!$token instanceof ApiToken
            || ($token->getExpireAt() !== NULL && $token->getExpireAt() < new DateTime())
            || array_intersect(self::GRANT_SCOPES, $token->getScopes()) === []
        ) {
            throw new ActionException('Invalid API key.', ActionException::UNAUTHORIZED);
        }
    }

    /**
     * Actions as connector groups for Dashboards -> Connectors: the connector metrics of an action
     * carry `node_id = action:<key>` and no topology.
     *
     * @param mixed[] $catalog
     *
     * @return mixed[]
     */
    public static function toConnectorGroups(array $catalog): array
    {
        $groups = [];
        foreach ($catalog as $action) {
            $key = is_string($action['key'] ?? NULL) ? $action['key'] : '';
            if ($key === '') {
                continue;
            }

            $groups[] = [
                'application' => is_string($action['app'] ?? NULL) ? $action['app'] : '',
                'key'         => $key,
                'name'        => is_string($action['name'] ?? NULL) ? $action['name'] : $key,
                'nodeIds'     => [sprintf('action:%s', $key)],
                'topologyIds' => [],
                'type'        => 'action',
            ];
        }

        return $groups;
    }

}
