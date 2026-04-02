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
use Hanaboso\UserBundle\Document\User;
use InvalidArgumentException;

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
     * @param string      $id
     * @param string|null $name
     * @param int|null    $level
     *
     * @return mixed[]
     * @throws AclException
     */
    public function updateGroup(string $id, ?string $name = NULL, ?int $level = NULL): array
    {
        $group = $this->findGroupOrFail($id);

        $dto = new GroupDto($group, $name);

        foreach ($group->getUsers() as $user) {
            $dto->addUser($user);
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
     * @param string $id
     *
     * @throws AclException
     */
    public function deleteGroup(string $id): void
    {
        $group = $this->findGroupOrFail($id);

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
        $this->findGroupOrFail($groupId);

        $this->groupManager->removeUserFromGroup(
            $this->findUserOrFail($userId),
            id: $groupId,
        );
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
        $rules = [];
        /** @var Rule $rule */
        foreach ($group->getRules() as $rule) {
            $rules[] = [
                'actions'      => $this->maskFactory->getActionsFromMask($rule->getActionMask()),
                'propertyMask' => $rule->getPropertyMask(),
                'resource'     => $rule->getResource(),
            ];
        }

        return [
            'id'         => $group->getId(),
            'level'      => $group->getLevel(),
            'name'       => $group->getName(),
            'rules'      => $rules,
            'usersCount' => count([...$group->getUsers()]),
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
        /** @var User $user */
        foreach ($group->getUsers() as $user) {
            $users[] = [
                'email' => $user->getEmail(),
                'id'    => $user->getId(),
            ];
        }

        $data['users'] = $users;

        return $data;
    }

}
