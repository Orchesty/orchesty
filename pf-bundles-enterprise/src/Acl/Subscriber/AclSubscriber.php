<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Acl\Subscriber;

use Doctrine\Persistence\ObjectRepository;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Exception\AclException;
use Hanaboso\AclBundle\Manager\AccessManager;
use Hanaboso\AclBundle\Repository\Document\GroupRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Database\Document\Category;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFrameworkEnterprise\Acl\Enum\ActionEnum;
use Hanaboso\PipesFrameworkEnterprise\Acl\PermissionPresets;
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

        '/api/applications/topologies/nodes' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::TOPOLOGY]],

        '/api/audit-logs' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::SETTINGS]],

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

        '/api/group' => [
            'DELETE' => [ActionEnum::DELETE, ResourceEnum::SETTINGS],
            'GET'    => [ActionEnum::READ, ResourceEnum::SETTINGS],
            'POST'   => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
            'PUT'    => [ActionEnum::WRITE, ResourceEnum::SETTINGS],
        ],

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
        '/api/nodes/connectors' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::CONNECTOR]],

        '/api/processes'            => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],
        '/api/processes/graph'      => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::OVERVIEW]],
        '/api/processes/topologies' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::TOPOLOGY]],
        '/api/processes/total'      => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::OVERVIEW]],

        '/api/progress' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::PROCESS]],

        '/api/resources/limiter' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::LIMITER]],

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
        '/api/user/me/groups'        => [],
        '/api/user/reset_password'   => [],
        '/api/user/whoami'           => [],
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
     * @var ObjectRepository<Category>
     */
    private ObjectRepository $categoryRepository;

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
        $this->categoryRepository = $dm->getRepository(Category::class);
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

        $groups = $this->groupRepository->getUserGroups($managedUser);

        foreach ($groups as $group) {
            if ($group->getLevel() === 0) {
                return;
            }
        }

        [$action, $resource, $topologyNames, $originalResource] = $match;

        $this->enforceSystemTopologyRestrictions($pathInfo, $request->getMethod(), $groups);

        if ($this->isAllowedByPreset($groups, $action, $originalResource)) {
            return;
        }

        if ($topologyNames !== []) {
            foreach ($topologyNames as $topologyName) {
                try {
                    $this->accessManager->isAllowed(
                        $action,
                        sprintf('%s:%s', $resource, $topologyName),
                        $managedUser,
                    );

                    return;
                } catch (AclException) {
                }
            }

            throw new AccessDeniedHttpException('Access denied.');
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
     * @param Group[] $groups
     * @param string  $action
     * @param string  $resource
     *
     * @return bool
     */
    private function isAllowedByPreset(array $groups, string $action, string $resource): bool
    {
        $presetNames = PermissionPresets::names();

        foreach ($groups as $group) {
            $groupName = $group->getName();

            if (!in_array($groupName, $presetNames, TRUE)) {
                continue;
            }

            $rules        = PermissionPresets::resolve($groupName);
            $baseResource = str_contains($resource, ':') ? (string) strstr($resource, ':', TRUE) : $resource;

            return isset($rules[$baseResource]) && in_array($action, $rules[$baseResource], TRUE);
        }

        return FALSE;
    }

    /**
     * @param string  $path
     * @param string  $method
     * @param Request $request
     *
     * @return array{string, string, string[], string}|null
     */
    private function processAcl(string $path, string $method, Request $request): ?array
    {
        if ($method === 'POST' && preg_match('#^/api/topologies/([a-f0-9]+)/run$#', $path, $m)) {
            return $this->resolveTopologyRun($m[1]);
        }

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
     * @param string $topologyId
     *
     * @return array{string, string, string[], string}
     */
    private function resolveTopologyRun(string $topologyId): array
    {
        $topology = $this->topologyRepository->find($topologyId);

        if ($topology !== NULL) {
            return [ActionEnum::RUN, ResourceEnum::TOPOLOGY, [$topology->getName()], ResourceEnum::TOPOLOGY];
        }

        return [ActionEnum::RUN, ResourceEnum::TOPOLOGY, [], ResourceEnum::TOPOLOGY];
    }

    /**
     * @param string               $matchedPrefix
     * @param array{string,string} $match
     * @param Request              $request
     *
     * @return array{string, string, string[], string}
     */
    private function getTopologyScope(string $matchedPrefix, array $match, Request $request): array
    {
        if (isset(self::TOPOLOGY_SCOPED_MAP[$matchedPrefix])) {
            $topologyNames = $this->getTopologyNames($request);

            if ($topologyNames !== []) {
                return [$match[0], self::TOPOLOGY_SCOPED_MAP[$matchedPrefix], $topologyNames, $match[1]];
            }
        }

        return [$match[0], $match[1], [], $match[1]];
    }

    /**
     * Block DELETE on system topologies entirely; require system_manager for WRITE.
     *
     * @param string  $path
     * @param string  $method
     * @param Group[] $groups
     */
    private function enforceSystemTopologyRestrictions(string $path, string $method, array $groups): void
    {
        if (!preg_match('#^/api/topologies/([a-f0-9]+)#', $path, $m)) {
            return;
        }

        if (!in_array($method, ['DELETE', 'PUT', 'PATCH', 'POST'], TRUE)) {
            return;
        }

        $topology = $this->topologyRepository->find($m[1]);

        if ($topology === NULL) {
            return;
        }

        $categoryId = $topology->getCategory();

        if ($categoryId === NULL) {
            return;
        }

        $category = $this->categoryRepository->find($categoryId);

        if ($category === NULL || !$category->isSystem()) {
            return;
        }

        if ($method === 'DELETE') {
            throw new AccessDeniedHttpException('System topologies cannot be deleted.');
        }

        $hasSystemManager = FALSE;
        foreach ($groups as $group) {
            if (in_array($group->getName(), [PermissionPresets::SYSTEM_MANAGER, PermissionPresets::SUPER_ADMIN], TRUE)
                || $group->getLevel() === 0) {
                $hasSystemManager = TRUE;

                break;
            }
        }

        if (!$hasSystemManager) {
            throw new AccessDeniedHttpException('System topologies require System Manager permissions.');
        }
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
