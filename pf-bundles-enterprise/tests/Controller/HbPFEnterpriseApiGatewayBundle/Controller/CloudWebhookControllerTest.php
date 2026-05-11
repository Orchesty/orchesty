<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Controller\HbPFEnterpriseApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller\CloudWebhookController;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudMemberSyncService;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service\HandoffSyncLock;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CloudWebhookControllerTest
 *
 * Coverage for the cloud session-handoff flow on the instance side.
 *
 * Focuses on the self-healing branch: when the cloud signs a handoff for
 * a user that does not yet exist in the instance Mongo, the controller must
 * (a) inline-provision that user from the payload, and (b) best-effort
 * trigger CloudUserSyncHandler::syncUsers to backfill the rest of the
 * instance member list. Existing-user behaviour and signature errors are
 * also covered to guard against regressions of the original contract.
 *
 * @package PipesFrameworkEnterpriseTests\Controller\HbPFEnterpriseApiGatewayBundle\Controller
 */
#[CoversClass(CloudWebhookController::class)]
#[AllowMockObjectsWithoutExpectations]
final class CloudWebhookControllerTest extends TestCase
{

    private const string SECRET = 'unit-test-instance-secret';

    /**
     * @return void
     */
    public function testHandoffReturnsTokenForExistingUserWithoutProvisioning(): void
    {
        $existing = $this->makeUser('user-id-1', 'member@example.com');

        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->expects(self::never())->method('provisionSingleUser');
        $userSync->expects(self::never())->method('syncUsers');

        $lock = $this->createMock(HandoffSyncLock::class);
        $lock->expects(self::never())->method('acquire');

        $controller = $this->makeController($existing, $userSync, $lock);

        $token   = $this->makeHandoffToken([
            'accountId'    => 'acc-1',
            'email'        => 'member@example.com',
            'instanceId'   => 'inst-1',
            'isOrgMember'  => TRUE,
            'name'         => 'Member',
            'picture'      => '',
            'role'         => 'DEVELOPER',
            'ts'           => $this->msNow(),
            'v'            => 2,
            'webhookToken' => 'wt-1',
        ]);
        $request = new Request([], [], [], [], [], [], Json::encode(['token' => $token]));

        $response = $controller->sessionHandoffAction($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = Json::decode((string) $response->getContent());
        self::assertSame('member@example.com', $body['email']);
        self::assertSame('user-id-1', $body['id']);
        self::assertNotEmpty($body['token']);
    }

    /**
     * @return void
     */
    public function testHandoffProvisionsMissingUserAndTriggersBackfill(): void
    {
        $provisioned = $this->makeUser('user-id-2', 'newcomer@example.com');

        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->expects(self::once())
            ->method('provisionSingleUser')
            ->with('newcomer@example.com', 'OWNER')
            ->willReturn($provisioned);
        $userSync->expects(self::once())
            ->method('syncUsers')
            ->with(self::callback(static fn(array $data): bool => ($data['token'] ?? NULL) === 'wt-2'))
            ->willReturn(['created' => 3, 'skipped' => 1, 'total' => 4]);

        $lock = $this->createMock(HandoffSyncLock::class);
        $lock->expects(self::once())->method('acquire')->with('inst-2')->willReturn(TRUE);
        $lock->expects(self::once())->method('release')->with('inst-2');

        $controller = $this->makeController(NULL, $userSync, $lock);

        $token   = $this->makeHandoffToken([
            'accountId'    => 'acc-2',
            'email'        => 'newcomer@example.com',
            'instanceId'   => 'inst-2',
            'isOrgMember'  => TRUE,
            'name'         => 'New Comer',
            'picture'      => '',
            'role'         => 'OWNER',
            'ts'           => $this->msNow(),
            'v'            => 2,
            'webhookToken' => 'wt-2',
        ]);
        $request = new Request([], [], [], [], [], [], Json::encode(['token' => $token]));

        $response = $controller->sessionHandoffAction($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return void
     */
    public function testHandoffSkipsBackfillWhenWebhookTokenMissing(): void
    {
        $provisioned = $this->makeUser('user-id-3', 'legacy@example.com');

        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->expects(self::once())
            ->method('provisionSingleUser')
            ->willReturn($provisioned);
        $userSync->expects(self::never())->method('syncUsers');

        $lock = $this->createMock(HandoffSyncLock::class);
        $lock->expects(self::never())->method('acquire');

        $controller = $this->makeController(NULL, $userSync, $lock);

        // Backward-compatible v1 payload (no webhookToken / instanceId).
        $token   = $this->makeHandoffToken([
            'email'       => 'legacy@example.com',
            'isOrgMember' => TRUE,
            'name'        => 'Legacy',
            'picture'     => '',
            'ts'          => $this->msNow(),
        ]);
        $request = new Request([], [], [], [], [], [], Json::encode(['token' => $token]));

        $response = $controller->sessionHandoffAction($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return void
     */
    public function testHandoffSkipsBackfillWhenLockNotAcquired(): void
    {
        $provisioned = $this->makeUser('user-id-4', 'parallel@example.com');

        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->expects(self::once())
            ->method('provisionSingleUser')
            ->willReturn($provisioned);
        $userSync->expects(self::never())->method('syncUsers');

        $lock = $this->createMock(HandoffSyncLock::class);
        $lock->expects(self::once())->method('acquire')->with('inst-4')->willReturn(FALSE);
        $lock->expects(self::never())->method('release');

        $controller = $this->makeController(NULL, $userSync, $lock);

        $token   = $this->makeHandoffToken([
            'accountId'    => 'acc-4',
            'email'        => 'parallel@example.com',
            'instanceId'   => 'inst-4',
            'isOrgMember'  => TRUE,
            'name'         => '',
            'picture'      => '',
            'role'         => 'DEVELOPER',
            'ts'           => $this->msNow(),
            'v'            => 2,
            'webhookToken' => 'wt-4',
        ]);
        $request = new Request([], [], [], [], [], [], Json::encode(['token' => $token]));

        $response = $controller->sessionHandoffAction($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return void
     */
    public function testHandoffReleasesLockEvenWhenBackfillThrows(): void
    {
        $provisioned = $this->makeUser('user-id-5', 'flaky@example.com');

        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->method('provisionSingleUser')->willReturn($provisioned);
        $userSync->method('syncUsers')->willThrowException(new RuntimeException('cloud unreachable'));

        $lock = $this->createMock(HandoffSyncLock::class);
        $lock->expects(self::once())->method('acquire')->with('inst-5')->willReturn(TRUE);
        $lock->expects(self::once())->method('release')->with('inst-5');

        $controller = $this->makeController(NULL, $userSync, $lock);

        $token   = $this->makeHandoffToken([
            'accountId'    => 'acc-5',
            'email'        => 'flaky@example.com',
            'instanceId'   => 'inst-5',
            'isOrgMember'  => TRUE,
            'name'         => '',
            'picture'      => '',
            'role'         => 'BILLING',
            'ts'           => $this->msNow(),
            'v'            => 2,
            'webhookToken' => 'wt-5',
        ]);
        $request = new Request([], [], [], [], [], [], Json::encode(['token' => $token]));

        $response = $controller->sessionHandoffAction($request);

        // Visitor must still be authenticated even though backfill failed.
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return void
     */
    public function testHandoffRejectsInvalidSignature(): void
    {
        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->expects(self::never())->method('provisionSingleUser');
        $userSync->expects(self::never())->method('syncUsers');

        $controller = $this->makeController(NULL, $userSync, $this->createMock(HandoffSyncLock::class));

        $payload     = [
            'email' => 'spoof@example.com',
            'ts'    => $this->msNow(),
            'v'     => 2,
        ];
        $payloadB64  = rtrim(strtr(base64_encode(Json::encode($payload)), '+/', '-_'), '=');
        $forgedToken = sprintf(
            '%s.%s',
            $payloadB64,
            rtrim(strtr(base64_encode('not-a-real-signature'), '+/', '-_'), '='),
        );
        $request     = new Request([], [], [], [], [], [], Json::encode(['token' => $forgedToken]));

        $response = $controller->sessionHandoffAction($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @return void
     */
    public function testHandoffRejectsExpiredToken(): void
    {
        $userSync = $this->createMock(CloudUserSyncHandler::class);
        $userSync->expects(self::never())->method('provisionSingleUser');

        $controller = $this->makeController(NULL, $userSync, $this->createMock(HandoffSyncLock::class));

        $token   = $this->makeHandoffToken([
            'email' => 'late@example.com',
            'ts'    => $this->msNow() - (10 * 60 * 1_000), // 10 minutes ago > 5min budget
            'v'     => 2,
        ]);
        $request = new Request([], [], [], [], [], [], Json::encode(['token' => $token]));

        $response = $controller->sessionHandoffAction($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @param User|NULL            $existingUser user the repo returns for findOneBy()
     * @param CloudUserSyncHandler $userSync
     * @param HandoffSyncLock      $lock
     */
    private function makeController(
        ?User $existingUser,
        CloudUserSyncHandler $userSync,
        HandoffSyncLock $lock,
    ): CloudWebhookController
    {
        $userRepo = $this->createMock(DocumentRepository::class);
        $userRepo->method('findOneBy')->willReturn($existingUser);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($userRepo);

        $security = $this->createMock(SecurityManager::class);
        $security->method('createToken')->willReturn('local-jwt');

        return new CloudWebhookController(
            $userSync,
            $this->createMock(CloudMemberSyncService::class),
            $lock,
            $security,
            $dm,
            self::SECRET,
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     */
    private function makeHandoffToken(array $payload): string
    {
        $payloadB64 = rtrim(strtr(base64_encode(Json::encode($payload)), '+/', '-_'), '=');
        $signature  = hash_hmac('sha256', $payloadB64, self::SECRET, TRUE);
        $sigB64     = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return sprintf('%s.%s', $payloadB64, $sigB64);
    }

    /**
     * @return float
     */
    private function msNow(): float
    {
        return microtime(TRUE) * 1_000;
    }

    /**
     * Create a User document fixture with a forced id (no setId() exists publicly).
     *
     * @param string $id
     * @param string $email
     *
     * @return User
     */
    private function makeUser(string $id, string $email): User
    {
        $user = new User();
        $user->setEmail($email);

        $idProp = new ReflectionProperty($user, 'id');
        $idProp->setValue($user, $id);

        return $user;
    }

}
