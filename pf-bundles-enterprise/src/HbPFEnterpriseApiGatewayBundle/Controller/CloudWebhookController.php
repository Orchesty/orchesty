<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudMemberSyncService;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
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
     * @param SecurityManager        $securityManager
     * @param DocumentManager        $dm
     * @param string                 $instanceSecret
     */
    public function __construct(
        private readonly CloudUserSyncHandler $cloudUserSyncHandler,
        private readonly CloudMemberSyncService $cloudMemberSyncService,
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
                return new JsonResponse(
                    ['message' => sprintf('User [%s] not found on this instance', $email)],
                    Response::HTTP_NOT_FOUND,
                );
            }

            $jwt = $this->securityManager->createToken(
                $user->getId(),
                $user->getEmail(),
                420,
            );

            return new JsonResponse([
                'email'    => $user->getEmail(),
                'id'       => $user->getId(),
                'settings' => [],
                'token'    => $jwt,
            ]);
        } catch (Throwable $t) {
            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

}
