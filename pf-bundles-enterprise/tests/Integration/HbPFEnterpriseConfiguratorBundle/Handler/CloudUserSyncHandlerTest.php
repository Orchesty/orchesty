<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
    public function testProvisionSingleUserCreatesUserWithDefaultGroupWhenRoleIsNull(): void
    {
        $userRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo = $this->createMock(DocumentRepository::class);
        $tmpRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => $cls === User::class ? $userRepo : $tmpRepo,
        );

        $captured = [];
        $groupMgr = $this->createMock(GroupManager::class);
        $groupMgr->method('addUserIntoGroup')->willReturnCallback(
            static function (User $u, ?string $id = NULL, ?string $groupName = NULL) use (&$captured): void {
                unset($id);
                $captured[] = ['email' => $u->getEmail(), 'group' => $groupName];
            },
        );

        $handler = $this->makeHandler($dm, $groupMgr);

        $user = $handler->provisionSingleUser('alice@example.com', NULL);

        self::assertSame('alice@example.com', $user->getEmail());
        self::assertCount(1, $captured);
        self::assertSame('user', $captured[0]['group']);
    }

    /**
     * @return void
     */
    public function testProvisionSingleUserMapsOwnerToAdminGroup(): void
    {
        $userRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo = $this->createMock(DocumentRepository::class);
        $tmpRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => $cls === User::class ? $userRepo : $tmpRepo,
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

        self::assertSame(['admin', 'admin'], $captured);
    }

    /**
     * @return void
     */
    public function testProvisionSingleUserMapsBillingAndDeveloperToUserGroup(): void
    {
        $userRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo = $this->createMock(DocumentRepository::class);
        $tmpRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => $cls === User::class ? $userRepo : $tmpRepo,
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

        $handler->provisionSingleUser('billing@example.com', 'BILLING');
        $handler->provisionSingleUser('dev@example.com', 'DEVELOPER');

        self::assertSame(['user', 'user'], $captured);
    }

    /**
     * @return void
     */
    public function testProvisionSingleUserIsIdempotentWhenUserExists(): void
    {
        $existing = new User();
        $existing->setEmail('present@example.com');

        $userRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn($existing);
        $tmpRepo = $this->createMock(DocumentRepository::class);
        $tmpRepo->method('findOneBy')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => $cls === User::class ? $userRepo : $tmpRepo,
        );
        $dm->expects(self::never())->method('persist');
        $dm->expects(self::never())->method('flush');

        $groupMgr = $this->createMock(GroupManager::class);
        $groupMgr->expects(self::never())->method('addUserIntoGroup');

        $handler = $this->makeHandler($dm, $groupMgr);

        $result = $handler->provisionSingleUser('present@example.com', 'OWNER');

        self::assertSame($existing, $result);
    }

    /**
     * @return void
     */
    public function testProvisionSingleUserRemovesTmpUserOnCreation(): void
    {
        $tmpUser = new TmpUser();
        $tmpUser->setEmail('pending@example.com');

        $userRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn(NULL);
        $tmpRepo = $this->createMock(DocumentRepository::class);
        $tmpRepo->method('findOneBy')->willReturn($tmpUser);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $cls) => $cls === User::class ? $userRepo : $tmpRepo,
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

        $handler->provisionSingleUser('pending@example.com', NULL);

        self::assertTrue($removeCalled, 'TmpUser for the provisioned e-mail must be removed.');
    }

    /**
     * @param DocumentManager $dm
     * @param GroupManager    $groupMgr
     *
     * @return CloudUserSyncHandler
     */
    private function makeHandler(DocumentManager $dm, GroupManager $groupMgr): CloudUserSyncHandler
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
            'http://cloud.local',
        );
    }

}
