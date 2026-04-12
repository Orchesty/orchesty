<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Document\Rule;
use Hanaboso\AclBundle\Dto\GroupDto;
use Hanaboso\AclBundle\Exception\AclException;
use Hanaboso\AclBundle\Factory\MaskFactory;
use Hanaboso\AclBundle\Manager\AccessManager;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFrameworkEnterprise\Acl\PermissionPresets;
use Hanaboso\UserBundle\Document\User;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Class EnterpriseGroupHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class EnterpriseGroupHandler
{

    /**
     * EnterpriseGroupHandler constructor.
     *
     * @param DocumentManager $dm
     * @param AccessManager   $accessManager
     * @param GroupManager    $groupManager
     * @param MaskFactory     $maskFactory
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly AccessManager $accessManager,
        private readonly GroupManager $groupManager,
        private readonly MaskFactory $maskFactory,
    )
    {
    }

    /**
     * @return mixed[]
     */
    public function listGroups(): array
    {
        /** @var Group[] $groups */
        $groups = $this->dm->getRepository(Group::class)->findAll();

        $items = [];
        foreach ($groups as $group) {
            $items[] = $this->serializeGroupSummary($group);
        }

        usort($items, static fn(array $a, array $b): int => $a['level'] <=> $b['level']);

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     */
    public function getGroup(string $id): array
    {
        $group = $this->findGroupOrFail($id);

        return $this->serializeGroupDetail($group);
    }

    /**
     * @param string $name
     * @param int    $level
     *
     * @return mixed[]
     * @throws AclException
     */
    public function createGroup(string $name, int $level = 999): array
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Group name cannot be empty.');
        }

        if (in_array($name, PermissionPresets::names(), TRUE)) {
            throw new LogicException(sprintf('Group name [%s] is reserved for a system preset.', $name));
        }

        if ($level < 1) {
            throw new AclException('Group level must be at least 1.');
        }

        /** @var Group $group */
        $group = $this->accessManager->addGroup($name);

        $group->setLevel($level);
        $this->dm->flush();

        return $this->serializeGroupDetail($group);
    }

    /**
     * @param string       $id
     * @param string|null  $name
     * @param int|null     $level
     * @param mixed[]|null $rules Array of [{resource: string, actions: string[]}]
     *
     * @return mixed[]
     * @throws AclException
     */
    public function updateGroup(string $id, ?string $name = NULL, ?int $level = NULL, ?array $rules = NULL): array
    {
        $group = $this->findGroupOrFail($id);

        $this->guardPresetGroup($group);

        $dto = new GroupDto($group, $name);

        foreach ($group->getUsers() as $user) {
            $dto->addUser($user);
        }

        if ($rules !== NULL) {
            $ruleData = [];
            foreach ($rules as $entry) {
                $resource = $entry['resource'] ?? '';
                $actions  = $entry['actions'] ?? [];

                if ($resource === '' || !is_array($actions) || $actions === []) {
                    continue;
                }

                $baseResource = str_contains($resource, ':')
                    ? strstr($resource, ':', TRUE)
                    : $resource;

                $actionMask = $this->maskFactory->maskActionFromYmlArray($actions, $baseResource);

                if ($actionMask === 0) {
                    continue;
                }

                $ruleData[] = [
                    'action_mask'   => $actionMask,
                    'property_mask' => 2,
                    'resource'      => $resource,
                ];
            }

            if ($ruleData !== []) {
                $dto->addRule(Rule::class, $ruleData);
            }
        } else {
            $existingRuleData = [];
            /** @var Rule $existingRule */
            foreach ($group->getRules() as $existingRule) {
                $existingRuleData[] = [
                    'action_mask'   => $existingRule->getActionMask(),
                    'property_mask' => $existingRule->getPropertyMask(),
                    'resource'      => $existingRule->getResource(),
                ];
            }
            if ($existingRuleData !== []) {
                $dto->addRule(Rule::class, $existingRuleData);
            }
        }

        /** @var Group $updatedGroup */
        $updatedGroup = $this->accessManager->updateGroup($dto);

        if ($level !== NULL) {
            if ($level < 1) {
                throw new AclException('Group level must be at least 1.');
            }

            $updatedGroup->setLevel($level);
            $this->dm->flush();
        }

        return $this->serializeGroupDetail($updatedGroup);
    }

    /**
     * @return mixed[]
     */
    public function getPermissionsSchema(): array
    {
        return $this->maskFactory->getAllowedList();
    }

    /**
     * @return mixed[]
     */
    public function getPresets(): array
    {
        /** @var Group[] $allGroups */
        $allGroups  = $this->dm->getRepository(Group::class)->findAll();
        $groupByName = [];
        foreach ($allGroups as $g) {
            $groupByName[$g->getName()] = $g->getId();
        }

        $presets = [];

        foreach (PermissionPresets::all() as $name => $preset) {
            $rules = [];
            foreach ($preset['rules'] as $resource => $actions) {
                $rules[] = ['resource' => $resource, 'actions' => $actions];
            }

            $presets[] = [
                'description' => $preset['description'],
                'groupId'     => $groupByName[$name] ?? NULL,
                'label'       => $preset['label'],
                'level'       => $preset['level'],
                'name'        => $name,
                'rules'       => $rules,
            ];
        }

        return $presets;
    }

    /**
     * Creates all preset groups in the database if they don't exist yet.
     * Preset groups have no Rule documents — their permissions are resolved from code.
     */
    public function ensurePresetGroups(): void
    {
        $repo          = $this->dm->getRepository(Group::class);
        $existingNames = [];

        /** @var Group $g */
        foreach ($repo->findAll() as $g) {
            $existingNames[] = $g->getName();
        }

        foreach (PermissionPresets::all() as $name => $preset) {
            if (in_array($name, $existingNames, TRUE)) {
                continue;
            }

            $group = new Group(NULL);
            $group->setName($name);
            $group->setLevel($preset['level']);
            $this->dm->persist($group);
        }

        $this->dm->flush();

        $collection = $this->dm->getDocumentCollection(Group::class);
        $collection->updateMany(
            ['owner' => ['$exists' => FALSE]],
            ['$set' => ['owner' => NULL]],
        );
    }

    /**
     * @param Group $group
     *
     * @return string|null
     */
    public function detectPreset(Group $group): ?string
    {
        return in_array($group->getName(), PermissionPresets::names(), TRUE)
            ? $group->getName()
            : NULL;
    }

    /**
     * @param string $id
     *
     * @throws AclException
     */
    public function deleteGroup(string $id): void
    {
        $group = $this->findGroupOrFail($id);

        $this->guardPresetGroup($group);

        $this->accessManager->removeGroup($group);
    }

    /**
     * @param string $groupId
     * @param string $userId
     *
     * @throws AclException
     */
    public function addUserToGroup(string $groupId, string $userId): void
    {
        $this->findGroupOrFail($groupId);
        $this->findUserOrFail($userId);

        $this->groupManager->addUserIntoGroup(
            $this->findUserOrFail($userId),
            id: $groupId,
        );
    }

    /**
     * @param string $groupId
     * @param string $userId
     *
     * @throws AclException
     */
    public function removeUserFromGroup(string $groupId, string $userId): void
    {
        $group = $this->findGroupOrFail($groupId);
        $user  = $this->findUserOrFail($userId);

        /** @phpstan-ignore method.nonObject */
        $users = $group->getUsers()->toArray();
        /** @phpstan-ignore method.nonObject */
        $group->getUsers()->clear();
        $this->dm->flush();

        foreach ($users as $key => $item) {
            if ($item->getId() === $user->getId()) {
                unset($users[$key]);

                break;
            }
        }
        $group->setUsers(array_values($users));

        $this->dm->flush();
    }

    /**
     * @param string $userId
     *
     * @return mixed[]
     */
    public function getUserGroups(string $userId): array
    {
        $user = $this->findUserOrFail($userId);

        $repo   = $this->dm->getRepository(Group::class);
        $groups = $repo->getUserGroups($user);

        $items = [];
        foreach ($groups as $group) {
            $items[] = $this->serializeGroupSummary($group);
        }

        usort($items, static fn(array $a, array $b): int => $a['level'] <=> $b['level']);

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    /**
     * Returns all non-preset groups with their permissions for a given topology.
     *
     * @param string $topologyId
     *
     * @return mixed[]
     */
    public function getTopologyAccess(string $topologyId): array
    {
        $topology     = $this->findTopologyOrFail($topologyId);
        $topologyName = $topology->getName();
        $scopedKey    = sprintf('topology:%s', $topologyName);

        /** @var Group[] $groups */
        $groups = $this->dm->getRepository(Group::class)->findAll();

        $result = [];
        foreach ($groups as $group) {
            if ($this->detectPreset($group) !== NULL) {
                continue;
            }

            /** @var Rule $rule */
            foreach ($group->getRules() as $rule) {
                if ($rule->getResource() === $scopedKey) {
                    $result[] = [
                        'actions'   => $this->maskFactory->getActionsFromMask($rule->getActionMask()),
                        'groupId'   => $group->getId(),
                        'groupName' => $group->getName(),
                    ];

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Sets topology-scoped permissions for access groups.
     *
     * @param string  $topologyId
     * @param mixed[] $accessList Array of [{groupId: string, actions: string[]}]
     *
     * @return mixed[]
     * @throws AclException
     */
    public function updateTopologyAccess(string $topologyId, array $accessList): array
    {
        $topology     = $this->findTopologyOrFail($topologyId);
        $topologyName = $topology->getName();
        $scopedKey    = sprintf('topology:%s', $topologyName);

        $newAccessByGroupId = [];
        foreach ($accessList as $entry) {
            $gid     = $entry['groupId'] ?? '';
            $actions = $entry['actions'] ?? [];

            if ($gid === '' || !is_array($actions) || $actions === []) {
                continue;
            }

            $newAccessByGroupId[$gid] = $actions;
        }

        /** @var Group[] $groups */
        $groups = $this->dm->getRepository(Group::class)->findAll();

        foreach ($groups as $group) {
            if ($this->detectPreset($group) !== NULL) {
                continue;
            }

            $groupId           = $group->getId();
            $hasNewAccess      = isset($newAccessByGroupId[$groupId]);
            $hadExistingAccess = FALSE;

            $otherRules = [];
            /** @var Rule $rule */
            foreach ($group->getRules() as $rule) {
                if ($rule->getResource() === $scopedKey) {
                    $hadExistingAccess = TRUE;
                } else {
                    $otherRules[] = [
                        'action_mask'   => $rule->getActionMask(),
                        'property_mask' => $rule->getPropertyMask(),
                        'resource'      => $rule->getResource(),
                    ];
                }
            }

            if (!$hasNewAccess && !$hadExistingAccess) {
                continue;
            }

            $dto = new GroupDto($group);

            foreach ($group->getUsers() as $user) {
                $dto->addUser($user);
            }

            $ruleData = $otherRules;

            if ($hasNewAccess) {
                $actionMask = $this->maskFactory->maskActionFromYmlArray($newAccessByGroupId[$groupId], 'topology');

                if ($actionMask > 0) {
                    $ruleData[] = [
                        'action_mask'   => $actionMask,
                        'property_mask' => 2,
                        'resource'      => $scopedKey,
                    ];
                }
            }

            if ($ruleData !== []) {
                $dto->addRule(Rule::class, $ruleData);
            }

            $this->accessManager->updateGroup($dto);
        }

        return $this->getTopologyAccess($topologyId);
    }

    /**
     * @param string $id
     *
     * @return Topology
     */
    private function findTopologyOrFail(string $id): Topology
    {
        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($id);

        if (!$topology) {
            throw new InvalidArgumentException(sprintf('Topology [%s] not found.', $id));
        }

        return $topology;
    }

    /**
     * @param Group $group
     *
     * @throws LogicException
     */
    private function guardPresetGroup(Group $group): void
    {
        if ($this->detectPreset($group) !== NULL) {
            throw new LogicException(
                sprintf('System preset group [%s] cannot be modified or deleted.', $group->getName()),
            );
        }
    }

    /**
     * @param string $id
     *
     * @return Group
     */
    private function findGroupOrFail(string $id): Group
    {
        /** @var Group|null $group */
        $group = $this->dm->getRepository(Group::class)->find($id);

        if (!$group) {
            throw new InvalidArgumentException(sprintf('Group [%s] not found.', $id));
        }

        return $group;
    }

    /**
     * @param string $id
     *
     * @return User
     */
    private function findUserOrFail(string $id): User
    {
        /** @var User|null $user */
        $user = $this->dm->getRepository(User::class)->find($id);

        if (!$user) {
            throw new InvalidArgumentException(sprintf('User [%s] not found.', $id));
        }

        return $user;
    }

    /**
     * @param Group $group
     *
     * @return mixed[]
     */
    private function serializeGroupSummary(Group $group): array
    {
        $presetName = $this->detectPreset($group);

        $rules = [];
        if ($presetName !== NULL) {
            foreach (PermissionPresets::resolve($presetName) as $resource => $actions) {
                $rules[] = [
                    'actions'      => $actions,
                    'propertyMask' => 2,
                    'resource'     => $resource,
                ];
            }
        } else {
            /** @var Rule $rule */
            foreach ($group->getRules() as $rule) {
                try {
                    $rules[] = [
                        'actions'      => $this->maskFactory->getActionsFromMask($rule->getActionMask()),
                        'propertyMask' => $rule->getPropertyMask(),
                        'resource'     => $rule->getResource(),
                    ];
                } catch (Throwable) {
                }
            }
        }

        $usersCount = 0;
        foreach ($group->getUsers() as $user) {
            try {
                /** @phpstan-ignore instanceof.alwaysTrue */
                if ($user instanceof User && $user->getEmail()) {
                    $usersCount++;
                }
            } catch (Throwable) {
            }
        }

        return [
            'id'         => $group->getId(),
            'level'      => $group->getLevel(),
            'name'       => $group->getName(),
            'preset'     => $presetName,
            'rules'      => $rules,
            'usersCount' => $usersCount,
        ];
    }

    /**
     * @param Group $group
     *
     * @return mixed[]
     */
    private function serializeGroupDetail(Group $group): array
    {
        $data = $this->serializeGroupSummary($group);

        $users = [];
        foreach ($group->getUsers() as $user) {
            try {
                /** @phpstan-ignore instanceof.alwaysTrue */
                if ($user instanceof User) {
                    $users[] = [
                        'email' => $user->getEmail(),
                        'id'    => $user->getId(),
                    ];
                }
            } catch (Throwable) {
            }
        }

        $data['users'] = $users;

        return $data;
    }

}
