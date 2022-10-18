<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey;

use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\JWTAuthenticator;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
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
 * Class ApiKeyAuthenticator
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey
 */
final class ApiKeyAuthenticator extends AbstractAuthenticator
{

    public const AUTH_HEADER   = 'X-Auth';
    public const AUTHORIZATION = 'Authorization';

    /**
     * ApiKeyAuthenticator constructor.
     *
     * @param JWTAuthenticator $jwtAuthenticator
     * @param string           $universalApiKey
     */
    public function __construct(private JWTAuthenticator $jwtAuthenticator, private string $universalApiKey)
    {
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has(self::AUTH_HEADER)
            || $request->headers->has(self::AUTHORIZATION)
            || $request->query->has(self::AUTHORIZATION);
    }

    /**
     * @param Request $request
     *
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        try {
            if ($request->headers->has(self::AUTHORIZATION) || $request->query->has(self::AUTHORIZATION)) {
                return $this->jwtAuthenticator->authenticate($request);
            }

            if ($request->headers->get(self::AUTH_HEADER) !== $this->universalApiKey || empty($this->universalApiKey)) {
                throw new SecurityManagerException(
                    'API key is not valid.',
                    SecurityManagerException::USER_OR_PASSWORD_NOT_VALID,
                );
            }

            $apiUser = new User();
            $apiUser
                ->setEmail('apiUser')
                ->setDeleted(FALSE);

            return new SelfValidatingPassport(
                new UserBadge(
                    $apiUser->getEmail(),
                    static fn() => $apiUser,
                ),
            );
        } catch (Throwable $t) {
            throw new AuthenticationException('Not valid token', $t->getCode(), $t);
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

}
