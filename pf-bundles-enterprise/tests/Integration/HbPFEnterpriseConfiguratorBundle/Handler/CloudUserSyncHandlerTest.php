<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFrameworkEnterprise\Acl\PermissionPresets;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\EnterpriseGroupHandler;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Class CloudUserSyncHandlerTest
 *
 * Coverage for CloudUserSyncHandler::provisionSingleUser — used by the cloud
 * session-handoff fallback to inline-create the visiting user when the
 * primary push user-sync webhook never delivered.
 *
 * @package PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler
 */
#[CoversClass(CloudUserSyncHandler::class)]
#[AllowMockObjectsWithoutExpectations]
final class CloudUserSyncHandlerTest extends TestCase
{

    /**
     * @return void
     */
    public function testProvisionSingleUserMapsOwnerAndAdminToSuperAdmin(): void
    {
        $userRepo  = $this->createMock(DocumentRepository::class);
        $tmpRepo   = $this->createMock(DocumentRepository::class);
        $groupRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo->method('findOneBy')->willReturn(NULL);
        $groupRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => match ($cls) {
                User::class    => $userRepo,
                Group::class   => $groupRepo,
                default        => $tmpRepo,
            },
        );

        $captured = [];
        $groupMgr = $this->createMock(GroupManager::class);
        $groupMgr->method('addUserIntoGroup')->willReturnCallback(
            static function (User $u, ?string $id = NULL, ?string $groupName = NULL) use (&$captured): void {
                unset($u, $id);
                $captured[] = $groupName;
            },
        );

        $handler = $this->makeHandler($dm, $groupMgr);

        $handler->provisionSingleUser('owner@example.com', 'OWNER');
        $handler->provisionSingleUser('admin@example.com', 'ADMIN');

        self::assertSame(
            [PermissionPresets::SUPER_ADMIN, PermissionPresets::SUPER_ADMIN],
            $captured,
        );
    }

    /**
     * Cloud DEVELOPER and BILLING roles are NOT auto-provisioned in the
     * worker — DEVELOPERs are invited from inside the worker via
     * AddUserModal, BILLING is cloud-only. provisionSingleUser must throw
     * for these roles so the caller (session-handoff or syncUsers) can
     * surface a clear error instead of creating a privilege-less user.
     *
     * @return void
     */
    public function testProvisionSingleUserRejectsNonOwnerAdminRoles(): void
    {
        $userRepo  = $this->createMock(DocumentRepository::class);
        $tmpRepo   = $this->createMock(DocumentRepository::class);
        $groupRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo->method('findOneBy')->willReturn(NULL);
        $groupRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => match ($cls) {
                User::class    => $userRepo,
                Group::class   => $groupRepo,
                default        => $tmpRepo,
            },
        );

        $groupMgr = $this->createMock(GroupManager::class);
        $groupMgr->expects(self::never())->method('addUserIntoGroup');

        $handler = $this->makeHandler($dm, $groupMgr);

        foreach (['DEVELOPER', 'BILLING', NULL] as $role) {
            $caught = NULL;
            try {
                $handler->provisionSingleUser('reject@example.com', $role);
            } catch (RuntimeException $e) {
                $caught = $e;
            }
            self::assertNotNull(
                $caught,
                sprintf('Expected RuntimeException for cloud role %s', $role ?? 'NULL'),
            );
        }
    }

    /**
     * @return void
     */
    public function testProvisionSingleUserReappliesGroupForExistingUser(): void
    {
        $existing = new User();
        $existing->setEmail('present@example.com');

        $userRepo  = $this->createMock(DocumentRepository::class);
        $tmpRepo   = $this->createMock(DocumentRepository::class);
        $groupRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn($existing);
        $tmpRepo->method('findOneBy')->willReturn(NULL);
        // Returning NULL signals the preset group has not yet been created
        // (or the user is not a member yet) -> ensureGroup must call
        // addUserIntoGroup so the role gets healed on the next webhook.
        $groupRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => match ($cls) {
                User::class    => $userRepo,
                Group::class   => $groupRepo,
                default        => $tmpRepo,
            },
        );
        $dm->expects(self::never())->method('persist');

        $captured = [];
        $groupMgr = $this->createMock(GroupManager::class);
        $groupMgr->method('addUserIntoGroup')->willReturnCallback(
            static function (User $u, ?string $id = NULL, ?string $groupName = NULL) use (&$captured): void {
                unset($u, $id);
                $captured[] = $groupName;
            },
        );

        $handler = $this->makeHandler($dm, $groupMgr);

        $result = $handler->provisionSingleUser('present@example.com', 'OWNER');

        self::assertSame($existing, $result);
        self::assertSame([PermissionPresets::SUPER_ADMIN], $captured);
    }

    /**
     * @return void
     */
    public function testProvisionSingleUserRemovesTmpUserOnCreation(): void
    {
        $tmpUser = new TmpUser();
        $tmpUser->setEmail('pending@example.com');

        $userRepo  = $this->createMock(DocumentRepository::class);
        $tmpRepo   = $this->createMock(DocumentRepository::class);
        $groupRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo->method('findOneBy')->willReturn($tmpUser);
        $groupRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => match ($cls) {
                User::class    => $userRepo,
                Group::class   => $groupRepo,
                default        => $tmpRepo,
            },
        );

        $removeCalled = FALSE;
        $dm->method('remove')->willReturnCallback(
            static function ($obj) use (&$removeCalled, $tmpUser): void {
                if ($obj === $tmpUser) {
                    $removeCalled = TRUE;
                }
            },
        );

        $handler = $this->makeHandler($dm, $this->createMock(GroupManager::class));

        $handler->provisionSingleUser('pending@example.com', 'ADMIN');

        self::assertTrue($removeCalled, 'TmpUser for the provisioned e-mail must be removed.');
    }

    /**
     * Provisioning on a freshly cloud-provisioned instance must not depend on
     * the local setupUser flow having ever run — preset groups
     * (`super_admin`, …) may not yet exist in the worker Mongo. Both
     * provisionSingleUser and syncUsers must therefore proactively call
     * EnterpriseGroupHandler::ensurePresetGroups() before any
     * GroupManager::addUserIntoGroup() attempt; otherwise the cloud caller
     * sees "Group [super_admin] was not found!".
     *
     * @return void
     */
    public function testEnsuresPresetGroupsBeforeGroupAssignment(): void
    {
        $userRepo  = $this->createMock(DocumentRepository::class);
        $tmpRepo   = $this->createMock(DocumentRepository::class);
        $groupRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo->method('findOneBy')->willReturn(NULL);
        $groupRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => match ($cls) {
                User::class    => $userRepo,
                Group::class   => $groupRepo,
                default        => $tmpRepo,
            },
        );

        $callOrder    = [];
        $groupHandler = $this->createMock(EnterpriseGroupHandler::class);
        $groupHandler->method('ensurePresetGroups')->willReturnCallback(
            static function () use (&$callOrder): void {
                $callOrder[] = 'ensurePresetGroups';
            },
        );

        $groupMgr = $this->createMock(GroupManager::class);
        $groupMgr->method('addUserIntoGroup')->willReturnCallback(
            static function (User $u, ?string $id = NULL, ?string $groupName = NULL) use (&$callOrder): void {
                unset($u, $id, $groupName);
                $callOrder[] = 'addUserIntoGroup';
            },
        );

        $handler = $this->makeHandler($dm, $groupMgr, $groupHandler);
        $handler->provisionSingleUser('owner@example.com', 'OWNER');

        self::assertSame(
            ['ensurePresetGroups', 'addUserIntoGroup'],
            $callOrder,
            'ensurePresetGroups must run before addUserIntoGroup so the preset Group documents exist.',
        );
    }

    /**
     * @param DocumentManager             $dm
     * @param GroupManager                $groupMgr
     * @param EnterpriseGroupHandler|null $groupHandler optional; tests that don't care
     *                                                  about ensurePresetGroups can omit it
     *                                                  and get a no-op mock.
     *
     * @return CloudUserSyncHandler
     */
    private function makeHandler(
        DocumentManager $dm,
        GroupManager $groupMgr,
        ?EnterpriseGroupHandler $groupHandler = NULL,
    ): CloudUserSyncHandler
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturnCallback(static fn(string $raw): string => sprintf('hashed:%s', $raw));

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->method('getPasswordHasher')->willReturn($hasher);

        return new CloudUserSyncHandler(
            $this->createMock(CurlManager::class),
            $dm,
            $factory,
            $groupMgr,
            $groupHandler ?? $this->createMock(EnterpriseGroupHandler::class),
            'http://cloud.local',
        );
    }

}
