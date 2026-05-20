<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudMemberSyncService;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudUserSyncHandler;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service\HandoffConsumeClient;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Service\HandoffSyncLock;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
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
     * @param HandoffConsumeClient   $handoffConsumeClient
     * @param SecurityManager        $securityManager
     * @param DocumentManager        $dm
     * @param string                 $instanceId
     * @param string                 $instanceSecret
     */
    public function __construct(
        private readonly CloudUserSyncHandler $cloudUserSyncHandler,
        private readonly CloudMemberSyncService $cloudMemberSyncService,
        private readonly HandoffSyncLock $handoffSyncLock,
        private readonly HandoffConsumeClient $handoffConsumeClient,
        private readonly SecurityManager $securityManager,
        private readonly DocumentManager $dm,
        private readonly string $instanceId,
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
        $jti             = NULL;
        $sourceIp        = $request->getClientIp() ?? '';
        $userAgent       = $request->headers->get('User-Agent') ?? '';
        $tokenInstanceId = '';
        $tokenEmail      = '';

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
                $this->logHandoffEvent('SIGNATURE_INVALID', [
                    'sourceIp'  => $sourceIp,
                    'userAgent' => $userAgent,
                ]);

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

            $tokenEmail      = is_string($payload['email'] ?? NULL) ? $payload['email'] : '';
            $ts              = $payload['ts'] ?? 0;
            $jti             = is_string($payload['jti'] ?? NULL) ? $payload['jti'] : NULL;
            $tokenInstanceId = (string) ($payload['instanceId'] ?? '');

            if (!$tokenEmail) {
                return new JsonResponse(
                    ['message' => 'Invalid handoff payload'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Audience binding: a valid HMAC alone is not enough — the token
            // also has to be MINTED FOR THIS INSTANCE. Without this check, a
            // compromised cloud account could request a handoff for
            // instance A, and (if both share the secret via some bug) the
            // signature would still verify on instance B.
            //
            // `instanceId` ENV is injected at deploy time from
            // `%orchesty_cloud_instance_id%` and never changes for the
            // lifetime of the pod. Empty value = misconfiguration => fail
            // closed.
            if ($this->instanceId === '' || $tokenInstanceId === '' || $tokenInstanceId !== $this->instanceId) {
                $this->logHandoffEvent('AUDIENCE_MISMATCH', [
                    'email'              => $tokenEmail,
                    'expectedInstanceId' => $this->instanceId,
                    'jti'                => $jti,
                    'sourceIp'           => $sourceIp,
                    'tokenInstanceId'    => $tokenInstanceId,
                    'userAgent'          => $userAgent,
                ]);

                return new JsonResponse(
                    ['message' => 'Handoff token audience mismatch'],
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            $ageMs = (microtime(TRUE) * 1_000) - $ts;
            if ($ageMs > self::HANDOFF_MAX_AGE_SECONDS * 1_000) {
                $this->logHandoffEvent('EXPIRED', [
                    'ageMs'      => (int) $ageMs,
                    'email'      => $tokenEmail,
                    'instanceId' => $tokenInstanceId,
                    'jti'        => $jti,
                    'sourceIp'   => $sourceIp,
                    'userAgent'  => $userAgent,
                ]);

                return new JsonResponse(
                    ['message' => 'Handoff token expired'],
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            // Single-use enforcement (P2.1 replay protection). Call cloud
            // BEFORE we hand out a local JWT, so a second arrival of the
            // same token can never succeed even if the first request is
            // still mid-flight (cloud does the atomic flip).
            //
            // Older tokens (v<3) may not carry `jti`. We require it on a
            // version-mismatch fail-closed basis: a missing jti is a clear
            // sign the cloud has been downgraded or the token was forged
            // outside the official issuance path.
            if ($jti === NULL || $jti === '') {
                $this->logHandoffEvent('SIGNATURE_INVALID', [
                    'email'      => $tokenEmail,
                    'instanceId' => $tokenInstanceId,
                    'reason'     => 'missing_jti',
                    'sourceIp'   => $sourceIp,
                    'userAgent'  => $userAgent,
                ]);

                return new JsonResponse(
                    ['message' => 'Handoff token missing identifier'],
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            $consumeResult = $this->handoffConsumeClient->consume($jti, $sourceIp, $userAgent);

            switch ($consumeResult['outcome']) {
                case HandoffConsumeClient::OUTCOME_CONSUMED:
                    // Happy path — fall through to issue local JWT.
                    break;
                case HandoffConsumeClient::OUTCOME_REPLAY_REJECTED:
                    $this->logHandoffEvent('REPLAY_REJECTED', [
                        'email'      => $tokenEmail,
                        'instanceId' => $tokenInstanceId,
                        'jti'        => $jti,
                        'sourceIp'   => $sourceIp,
                        'userAgent'  => $userAgent,
                    ]);

                    return new JsonResponse(
                        ['message' => 'Handoff token already used'],
                        Response::HTTP_UNAUTHORIZED,
                    );
                case HandoffConsumeClient::OUTCOME_EXPIRED:
                    $this->logHandoffEvent('EXPIRED', [
                        'email'      => $tokenEmail,
                        'instanceId' => $tokenInstanceId,
                        'jti'        => $jti,
                        'reason'     => 'cloud_reported_expired',
                        'sourceIp'   => $sourceIp,
                        'userAgent'  => $userAgent,
                    ]);

                    return new JsonResponse(
                        ['message' => 'Handoff token expired'],
                        Response::HTTP_UNAUTHORIZED,
                    );
                case HandoffConsumeClient::OUTCOME_NOT_FOUND:
                case HandoffConsumeClient::OUTCOME_AUDIENCE_MISMATCH:
                    $this->logHandoffEvent('SIGNATURE_INVALID', [
                        'email'      => $tokenEmail,
                        'instanceId' => $tokenInstanceId,
                        'jti'        => $jti,
                        'outcome'    => $consumeResult['outcome'],
                        'sourceIp'   => $sourceIp,
                        'userAgent'  => $userAgent,
                    ]);

                    return new JsonResponse(
                        ['message' => 'Handoff token rejected'],
                        Response::HTTP_UNAUTHORIZED,
                    );
                case HandoffConsumeClient::OUTCOME_NOT_CONFIGURED:
                case HandoffConsumeClient::OUTCOME_UNREACHABLE:
                default:
                    // Cloud is unreachable. We intentionally FAIL CLOSED:
                    // without consume confirmation we have no way to
                    // prevent replay, so the user must retry. The cloud
                    // FE detects this 503 and shows the
                    // /instance-handoff/:id retry view.
                    $this->logHandoffEvent('CONSUME_UNREACHABLE', [
                        'email'      => $tokenEmail,
                        'instanceId' => $tokenInstanceId,
                        'jti'        => $jti,
                        'message'    => $consumeResult['message'] ?? '',
                        'sourceIp'   => $sourceIp,
                        'userAgent'  => $userAgent,
                    ]);

                    return new JsonResponse(
                        ['message' => 'Session handoff temporarily unavailable, please retry'],
                        Response::HTTP_SERVICE_UNAVAILABLE,
                    );
            }

            /** @var User|null $user */
            $user = $this->dm->getRepository(User::class)->findOneBy([
                'deleted' => FALSE,
                'email'   => $tokenEmail,
            ]);

            $cloudRole       = $payload['role'] ?? NULL;
            $webhookTok      = $payload['webhookToken'] ?? NULL;
            $linkInviteToken = $payload['linkInviteToken'] ?? NULL;

            if (!$user) {
                // Self-healing handoff: cloud guarantees membership before
                // signing this token, so we trust the payload and provision
                // the visiting user here. We then best-effort dotáhneme zbylé
                // členy tak, aby další uživatelé téže instance nemuseli
                // čekat na příští webhook.
                error_log(sprintf(
                    '[handoff] inline provision email=%s instanceId=%s',
                    $tokenEmail,
                    $tokenInstanceId,
                ));

                $user = $this->cloudUserSyncHandler->provisionSingleUser(
                    $tokenEmail,
                    is_string($cloudRole) ? $cloudRole : NULL,
                );

                if (is_string($webhookTok) && $webhookTok !== '') {
                    if ($this->handoffSyncLock->acquire($tokenInstanceId)) {
                        try {
                            $result = $this->cloudUserSyncHandler->syncUsers(['token' => $webhookTok]);
                            error_log(sprintf(
                                '[handoff] backfill triggered instanceId=%s result=%s',
                                $tokenInstanceId,
                                Json::encode($result),
                            ));
                        } catch (Throwable $t) {
                            // Backfill is best-effort; primární cíl (provision návštěvníka) splněn.
                            error_log(sprintf(
                                '[handoff] backfill failed instanceId=%s error=%s',
                                $tokenInstanceId,
                                $t->getMessage(),
                            ));
                        } finally {
                            $this->handoffSyncLock->release($tokenInstanceId);
                        }
                    }
                }
            }

            // Optional invite linking: when the handoff payload carries a
            // `linkInviteToken`, we accept the invite immediately so the
            // first visit also activates the user. This is the "Invite A)"
            // flow — invites stay per-instance, but the user lands on the
            // instance already authenticated via cloud handoff.
            if (is_string($linkInviteToken) && $linkInviteToken !== '') {
                try {
                    $this->cloudUserSyncHandler->acceptInviteForUser($user, $linkInviteToken);
                } catch (Throwable $t) {
                    // Don't break the handoff if invite activation fails.
                    // Cloud-side audit already shows the user is meant to
                    // be in this instance; the worst case is the invite
                    // row remains PENDING and admin sees it in the UI.
                    error_log(sprintf(
                        '[handoff] linkInviteToken activation failed instanceId=%s error=%s',
                        $tokenInstanceId,
                        $t->getMessage(),
                    ));
                }
            }

            $jwt = $this->securityManager->createToken(
                $user->getId(),
                $user->getEmail(),
                420,
            );

            $picture     = $payload['picture'] ?? '';
            $isOrgMember = $payload['isOrgMember'] ?? FALSE;

            $this->logHandoffEvent('CONSUMED', [
                'email'      => $user->getEmail(),
                'instanceId' => $tokenInstanceId,
                'jti'        => $jti,
                'role'       => is_string($cloudRole) ? $cloudRole : NULL,
                'sourceIp'   => $sourceIp,
                'userAgent'  => $userAgent,
                'userId'     => $user->getId(),
            ]);

            return new JsonResponse([
                'email'       => $user->getEmail(),
                'id'          => $user->getId(),
                'isOrgMember' => (bool) $isOrgMember,
                'picture'     => $picture,
                'settings'    => [],
                'token'       => $jwt,
            ]);
        } catch (Throwable $t) {
            $this->logHandoffEvent('ERROR', [
                'jti'       => $jti,
                'message'   => $t->getMessage(),
                'sourceIp'  => $sourceIp,
                'userAgent' => $userAgent,
            ]);

            return new JsonResponse(
                ['message' => $t->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Verify an instance-side invite token from the cloud frontend.
     *
     * Called by the cloud `/accept-invite/:token` route via the cloud BE:
     *   cloud FE  ->  cloud BE  ->  THIS endpoint (instance BE)
     *
     * Authenticated by the shared `instanceSecret` because there is no
     * cloud user JWT yet (the invitee may not be a cloud user at all).
     * Returns the e-mail tied to the token so the cloud sign-in page can
     * prefill / verify the user's address; never returns the token hash.
     *
     * POST /cloud/verify-invite
     * Body: { token: string, instanceSecret: string }
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/cloud/verify-invite', methods: ['POST'])]
    public function verifyInviteAction(Request $request): Response
    {
        try {
            if ($this->instanceSecret === '') {
                return new JsonResponse(['message' => 'Cloud mode is not configured'], Response::HTTP_BAD_REQUEST);
            }

            $data           = Json::decode($request->getContent());
            $token          = is_string($data['token'] ?? NULL) ? $data['token'] : '';
            $instanceSecret = is_string($data['instanceSecret'] ?? NULL) ? $data['instanceSecret'] : '';

            if ($token === '' || $instanceSecret === '') {
                return new JsonResponse(
                    ['message' => 'token and instanceSecret are required'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            // Constant-time equality to avoid leaking the configured secret
            // through response-timing side channels.
            if (!hash_equals($this->instanceSecret, $instanceSecret)) {
                return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
            }

            $tokenDoc = $this->dm->getRepository(Token::class)
                ->findOneBy(['hash' => $token]);

            if (!$tokenDoc) {
                return new JsonResponse(['message' => 'Invalid or expired invite'], Response::HTTP_NOT_FOUND);
            }

            // Resolve the e-mail from whichever side of the relation is set.
            // After provisioning, the TmpUser may already be gone but the
            // Token row can survive briefly until cleanup runs — accept both.
            $email = '';
            $tmp   = $tokenDoc->getTmpUser();
            $usr   = $tokenDoc->getUser();
            if ($tmp instanceof TmpUser) {
                $email = $tmp->getEmail();
            } elseif ($usr instanceof User) {
                $email = $usr->getEmail();
            }

            if ($email === '') {
                return new JsonResponse(['message' => 'Invite not bound to a user'], Response::HTTP_GONE);
            }

            return new JsonResponse([
                'email' => $email,
            ]);
        } catch (Throwable $t) {
            return new JsonResponse(['message' => $t->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Structured one-line JSON log for handoff lifecycle events on the
     * instance side. The cloud has its own `handoff_audit_logs` table; this
     * one is for forensics that the cloud cannot see (e.g. SIGNATURE_INVALID
     * never reaches the cloud).
     *
     * @param string  $event
     * @param mixed[] $context
     */
    private function logHandoffEvent(string $event, array $context = []): void
    {
        try {
            error_log(Json::encode([
                'context'   => $context,
                'event'     => sprintf('handoff_%s', strtolower($event)),
                'instance'  => $this->instanceId,
                'timestamp' => date('c'),
            ]));
        } catch (Throwable) {
            // Never throw out of a logger.
        }
    }

}
