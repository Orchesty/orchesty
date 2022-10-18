<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Authenticator;

use Hanaboso\Applinth\Handler\AuthorizationHandler;
use Hanaboso\Applinth\Manager\AuthorizationManager;
use Hanaboso\UserBundle\Document\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Throwable;

/**
 * Class EndUserAuthenticator
 *
 * @package Hanaboso\Applinth\Authenticator
 */
final class EndUserAuthenticator extends AbstractAuthenticator
{

    public const AUTHORIZATION = 'Authorization';

    /**
     * @var mixed[]
     */
    private array $loggedUser = [];

    /**
     * EndUserAuthenticator constructor.
     *
     * @param AuthorizationManager $manager
     */
    public function __construct(private readonly AuthorizationManager $manager)
    {
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        $request;

        return TRUE;
    }

    /**
     * @param Request $request
     *
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get(self::AUTHORIZATION) ?? $request->query->get(self::AUTHORIZATION) ?? '';

        if (empty($token)) {
            throw new AuthenticationException('Missing token');
        }

        try {
            $this->loggedUser = $this->manager->payloadFromJws($token);

            $apiUser = new User();
            $apiUser
                ->setEmail($this->loggedUser[AuthorizationHandler::EU_SUB])
                ->setDeleted(FALSE);

            return new SelfValidatingPassport(
                new UserBadge(
                    $apiUser->getEmail(),
                    static fn() => $apiUser,
                ),
            );
        } catch (Throwable $t) {
            throw new AuthenticationException($t->getMessage());
        }
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $request;
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $firewallName
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request;
        $token;
        $firewallName;

        return NULL;
    }

    /**
     * @return string
     */
    public function getAuthUser(): string
    {
        return $this->loggedUser[AuthorizationHandler::EU_SUB];
    }

    /**
     * @return string
     */
    public function getRootKey(): string
    {
        return $this->loggedUser[AuthorizationHandler::SUB];
    }

}
