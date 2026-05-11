<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudMemberSyncService;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service\HandoffSyncLock;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class CloudWebhookController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class CloudWebhookController extends AbstractController
{

    private const int HANDOFF_MAX_AGE_SECONDS = 300;

    /**
     * CloudWebhookController constructor.
     *
     * @param CloudUserSyncHandler   $cloudUserSyncHandler
     * @param CloudMemberSyncService $cloudMemberSyncService
     * @param HandoffSyncLock        $handoffSyncLock
     * @param SecurityManager        $securityManager
     * @param DocumentManager        $dm
     * @param string                 $instanceSecret
     */
    public function __construct(
        private readonly CloudUserSyncHandler $cloudUserSyncHandler,
        private readonly CloudMemberSyncService $cloudMemberSyncService,
        private readonly HandoffSyncLock $handoffSyncLock,
        private readonly SecurityManager $securityManager,
        private readonly DocumentManager $dm,
        private readonly string $instanceSecret,
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/orchesty/webhook/users', methods: ['POST'])]
    public function syncUsersAction(Request $request): Response
    {
        try {
            $data   = Json::decode($request->getContent());
            $result = $this->cloudUserSyncHandler->syncUsers($data);

            return new JsonResponse($result);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Proxies account user search to the cloud backend.
     * Used by the enterprise frontend "Add from account" tab.
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/cloud/account-users', methods: ['GET'])]
    public function accountUsersAction(Request $request): Response
    {
        try {
            $query = $request->query->getString('q', '');
            $users = $this->cloudMemberSyncService->searchAccountUsers($query);

            return new JsonResponse(['users' => $users]);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Validates an HMAC-signed handoff token from the cloud and returns a
     * local session JWT so the user is seamlessly authenticated on the instance.
     *
     * POST /cloud/session-handoff  (public — no JWT required)
     * Body: { "token": "<base64url-payload>.<base64url-signature>" }
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/cloud/session-handoff', methods: ['POST'])]
    public function sessionHandoffAction(Request $request): Response
    {
        try {
            if ($this->instanceSecret === '') {
                return new JsonResponse(
                    ['message' => 'Cloud mode is not configured'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $data  = Json::decode($request->getContent());
            $token = $data['token'] ?? '';
            if (!$token || !is_string($token)) {
                return new JsonResponse(
                    ['message' => 'Missing handoff token'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return new JsonResponse(
                    ['message' => 'Malformed handoff token'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            [$payloadB64, $signature] = $parts;

            $expectedSig = hash_hmac('sha256', $payloadB64, $this->instanceSecret, TRUE);
            $expectedB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

            if (!hash_equals($expectedB64, $signature)) {
                return new JsonResponse(
                    ['message' => 'Invalid handoff token signature'],
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            $decoded = base64_decode(strtr($payloadB64, '-_', '+/'), TRUE);
            if ($decoded === FALSE) {
                return new JsonResponse(
                    ['message' => 'Cannot decode handoff payload'],
                    Response::HTTP_BAD_REQUEST,
                );
            }
            $payload = Json::decode($decoded);

            $email = $payload['email'] ?? '';
            $ts    = $payload['ts'] ?? 0;
            if (!$email) {
                return new JsonResponse(
                    ['message' => 'Invalid handoff payload'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $ageMs = (microtime(TRUE) * 1_000) - $ts;
            if ($ageMs > self::HANDOFF_MAX_AGE_SECONDS * 1_000) {
                return new JsonResponse(
                    ['message' => 'Handoff token expired'],
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            /** @var User|null $user */
            $user = $this->dm->getRepository(User::class)->findOneBy([
                'deleted' => FALSE,
                'email'   => $email,
            ]);

            if (!$user) {
                // Self-healing handoff: cloud guarantees membership before
                // signing this token, so we trust the payload and provision
                // the visiting user here. We then best-effort dotáhneme zbylé
                // členy tak, aby další uživatelé téže instance nemuseli
                // čekat na příští webhook.
                $cloudRole  = $payload['role'] ?? NULL;
                $instanceId = (string) ($payload['instanceId'] ?? '');
                $webhookTok = $payload['webhookToken'] ?? NULL;

                error_log(sprintf(
                    '[handoff] inline provision email=%s instanceId=%s',
                    $email,
                    $instanceId,
                ));

                $user = $this->cloudUserSyncHandler->provisionSingleUser(
                    (string) $email,
                    is_string($cloudRole) ? $cloudRole : NULL,
                );

                if (is_string($webhookTok) && $webhookTok !== '' && $instanceId !== '') {
                    if ($this->handoffSyncLock->acquire($instanceId)) {
                        try {
                            $result = $this->cloudUserSyncHandler->syncUsers(['token' => $webhookTok]);
                            error_log(sprintf(
                                '[handoff] backfill triggered instanceId=%s result=%s',
                                $instanceId,
                                Json::encode($result),
                            ));
                        } catch (Throwable $t) {
                            // Backfill is best-effort; primární cíl (provision návštěvníka) splněn.
                            error_log(sprintf(
                                '[handoff] backfill failed instanceId=%s error=%s',
                                $instanceId,
                                $t->getMessage(),
                            ));
                        } finally {
                            $this->handoffSyncLock->release($instanceId);
                        }
                    }
                }
            }

            $jwt = $this->securityManager->createToken(
                $user->getId(),
                $user->getEmail(),
                420,
            );

            $picture     = $payload['picture'] ?? '';
            $isOrgMember = $payload['isOrgMember'] ?? FALSE;

            return new JsonResponse([
                'email'       => $user->getEmail(),
                'id'          => $user->getId(),
                'isOrgMember' => (bool) $isOrgMember,
                'picture'     => $picture,
                'settings'    => [],
                'token'       => $jwt,
            ]);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

}
