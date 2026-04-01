<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Acl\Subscriber;

use Doctrine\Persistence\ObjectRepository;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Enum\ActionEnum;
use Hanaboso\AclBundle\Exception\AclException;
use Hanaboso\AclBundle\Manager\AccessManager;
use Hanaboso\AclBundle\Repository\Document\GroupRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFrameworkEnterprise\ResourceEnum;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\String\Json;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AclSubscriber
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Acl\Subscriber
 */
final class AclSubscriber implements EventSubscriberInterface
{

    private const array ACL_PREFIX_MAP = [
        '/api/apiTokens' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::SETTINGS],
            'GET'    => [ActionEnum::READ, ResourceEnum::SETTINGS],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
        ],

        '/api/applications' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::APPLICATION],
            'GET'    => [ActionEnum::READ, ResourceEnum::APPLICATION],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::APPLICATION],
            'PUT'    => [ActionEnum::WRITE, ResourceEnum::APPLICATION],
        ],

        '/api/audit/entities' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::SETTINGS],
            'GET'    => [ActionEnum::READ, ResourceEnum::SETTINGS],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
            'PUT'    => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
        ],

        '/api/categories' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::TOPOLOGY],
            'GET'    => [ActionEnum::READ, ResourceEnum::TOPOLOGY],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::TOPOLOGY],
            'PUT'    => [ActionEnum::WRITE, ResourceEnum::TOPOLOGY],
        ],

        '/api/cloud/account-users' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::USER]],

        '/api/dashboards' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::OVERVIEW]],

        '/api/logs' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::LOGS]],

        '/api/metrics/connectors' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::CONNECTOR]],
        '/api/metrics/limits'     => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::LIMITER]],
        '/api/metrics/processes'  => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],
        '/api/metrics/requests'   => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],
        '/api/metrics/topology'   => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],
        '/api/metrics/user-tasks' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::USER_TASK]],

        '/api/nodes' => [
            'GET'   => [ActionEnum::READ, ResourceEnum::TOPOLOGY],
            'PATCH' => [ActionEnum::WRITE, ResourceEnum::SCHEDULED_TASK],
        ],

        '/api/processes'            => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],
        '/api/processes/graph'      => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::OVERVIEW]],
        '/api/processes/topologies' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::TOPOLOGY]],
        '/api/processes/total'      => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::OVERVIEW]],

        '/api/progress' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],

        '/api/sdks' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::SETTINGS],
            'GET'    => [ActionEnum::READ, ResourceEnum::SETTINGS],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
            'PUT'    => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
        ],

        '/api/topologies' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::TOPOLOGY],
            'GET'    => [ActionEnum::READ, ResourceEnum::TOPOLOGY],
            'PATCH'  => [ActionEnum::WRITE, ResourceEnum::TOPOLOGY],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::TOPOLOGY],
            'PUT'    => [ActionEnum::WRITE, ResourceEnum::TOPOLOGY],
        ],
        '/api/topologies/cron' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::SCHEDULED_TASK]],

        '/api/user-task' => [
            'DEFAULT' => [ActionEnum::WRITE, ResourceEnum::USER_TASK],
            'GET'     => [ActionEnum::READ, ResourceEnum::USER_TASK],
        ],

        '/api/user/' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::USER],
            'GET'    => [ActionEnum::READ, ResourceEnum::USER],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::USER],
        ],
        '/api/user/add-from-account' => ['DEFAULT' => [ActionEnum::WRITE, ResourceEnum::USER]],
        '/api/user/change_password'  => [],
        '/api/user/check_logged'     => [],
        '/api/user/exists'           => [],
        '/api/user/invite'           => ['DEFAULT' => [ActionEnum::WRITE, ResourceEnum::USER]],
        '/api/user/invited'          => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::USER],
            'GET'    => [ActionEnum::READ, ResourceEnum::USER],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::USER],
        ],
        '/api/user/list'             => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::USER]],
        '/api/user/logout'           => [],
        '/api/user/reset_password'   => [],
    ];

    private const array TOPOLOGY_SCOPED_MAP = [
        '/api/logs'              => ResourceEnum::TOPOLOGY_LOG,
        '/api/metrics/processes' => ResourceEnum::TOPOLOGY_METRICS,
        '/api/metrics/requests'  => ResourceEnum::TOPOLOGY_METRICS,
        '/api/metrics/topology'  => ResourceEnum::TOPOLOGY_METRICS,
        '/api/processes'         => ResourceEnum::TOPOLOGY_PROCESS,
        '/api/progress'          => ResourceEnum::TOPOLOGY_PROCESS,
        '/api/user-task'         => ResourceEnum::TOPOLOGY_TRASH,
    ];

    /**
     * @var ObjectRepository<User>
     */
    private ObjectRepository $userRepository;

    /**
     * @var ObjectRepository<Group>&GroupRepository
     */
    private ObjectRepository $groupRepository;

    /**
     * @var ObjectRepository<Topology>
     */
    private ObjectRepository $topologyRepository;

    /**
     * AclSubscriber constructor.
     *
     * @param AccessManager          $accessManager
     * @param Security               $security
     * @param DatabaseManagerLocator $dml
     */
    public function __construct(
        private readonly AccessManager $accessManager,
        private readonly Security $security,
        DatabaseManagerLocator $dml,
    )
    {
        $dm                       = $dml->get();
        $this->userRepository     = $dm->getRepository(User::class);
        $this->groupRepository    = $dm->getRepository(Group::class);
        $this->topologyRepository = $dm->getRepository(Topology::class);
    }

    /**
     * @param ControllerEvent $event
     *
     * @return void
     */
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request  = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $match    = $this->processAcl($pathInfo, $request->getMethod(), $request);

        if ($match === NULL) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $managedUser = $this->userRepository->find($user->getId());

        if (!$managedUser instanceof User) {
            return;
        }

        foreach ($this->groupRepository->getUserGroups($managedUser) as $group) {
            if ($group->getLevel() === 0) {
                return;
            }
        }

        [$action, $resource, $topologyNames] = $match;

        if ($topologyNames !== []) {
            $hasGenericAccess = NULL;

            foreach ($topologyNames as $topologyName) {
                try {
                    $this->accessManager->isAllowed(
                        $action,
                        sprintf('%s:%s', $resource, $topologyName),
                        $managedUser,
                    );

                    continue;
                } catch (AclException) {
                }

                if ($hasGenericAccess === NULL) {
                    try {
                        $this->accessManager->isAllowed($action, $resource, $managedUser);
                        $hasGenericAccess = TRUE;
                    } catch (AclException $e) {
                        throw new AccessDeniedHttpException('Access denied.', $e);
                    }
                }
            }

            return;
        }

        try {
            $this->accessManager->isAllowed($action, $resource, $managedUser);
        } catch (AclException $e) {
            throw new AccessDeniedHttpException('Access denied.', $e);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    /**
     * @param string  $path
     * @param string  $method
     * @param Request $request
     *
     * @return array{string, string, string[]}|null
     */
    private function processAcl(string $path, string $method, Request $request): ?array
    {
        $bestPrefix  = NULL;
        $bestMethods = NULL;

        foreach (self::ACL_PREFIX_MAP as $prefix => $methods) {
            if (str_starts_with($path, $prefix) && ($bestPrefix === NULL || strlen($prefix) > strlen($bestPrefix))) {
                $bestPrefix  = $prefix;
                $bestMethods = $methods;
            }
        }

        if ($bestPrefix === NULL) {
            return NULL;
        }

        $match = $bestMethods[$method] ?? $bestMethods['DEFAULT'] ?? NULL;

        return $match !== NULL ? $this->getTopologyScope($bestPrefix, $match, $request) : NULL;
    }

    /**
     * @param string               $matchedPrefix
     * @param array{string,string} $match
     * @param Request              $request
     *
     * @return array{string, string, string[]}
     */
    private function getTopologyScope(string $matchedPrefix, array $match, Request $request): array
    {
        if (isset(self::TOPOLOGY_SCOPED_MAP[$matchedPrefix])) {
            $topologyNames = $this->getTopologyNames($request);

            if ($topologyNames !== []) {
                return [$match[0], self::TOPOLOGY_SCOPED_MAP[$matchedPrefix], $topologyNames];
            }
        }

        return [$match[0], $match[1], []];
    }

    /**
     * @param Request $request
     *
     * @return string[]
     */
    private function getTopologyNames(Request $request): array
    {
        $topologyIds = $this->getTopologyIds($request);

        if ($topologyIds === []) {
            return [];
        }

        $topologyNames = [];

        foreach ($topologyIds as $topologyId) {
            $topology = $this->topologyRepository->find($topologyId);

            if ($topology !== NULL) {
                $topologyNames[] = $topology->getName();
            }
        }

        return array_values(array_unique($topologyNames));
    }

    /**
     * @param Request $request
     *
     * @return string[]
     */
    private function getTopologyIds(Request $request): array
    {
        $rawFilter = $request->query->get('filter', '');

        if ($rawFilter !== '') {
            try {
                $filter = Json::decode($rawFilter);
            } catch (JsonException) {
                return [];
            }

            if (isset($filter['filter']) && is_array($filter['filter'])) {
                foreach ($filter['filter'] as $group) {
                    if (!is_array($group)) {
                        continue;
                    }

                    foreach ($group as $condition) {
                        if (($condition['column'] ?? NULL) === 'topologyId') {
                            $value = $condition['value'] ?? NULL;

                            if (is_string($value)) {
                                return [$value];
                            }

                            if (is_array($value)) {
                                return array_values(array_filter($value, 'is_string'));
                            }

                            return [];
                        }
                    }
                }
            }
        }

        $bodyTopologyId = $request->request->get('topologyId');

        if (is_string($bodyTopologyId) && $bodyTopologyId !== '') {
            return [$bodyTopologyId];
        }

        $routeTopologyId = $request->attributes->get('topologyId')
            ?? $request->attributes->get('topology');

        if (is_string($routeTopologyId) && $routeTopologyId !== '') {
            return [$routeTopologyId];
        }

        return [];
    }

}
