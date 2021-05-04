<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey;

use Hanaboso\UserBundle\Document\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class ApiKeyAuthenticator
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey
 */
final class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{

    public const AUTH_HEADER = 'X-Auth';

    /**
     * ApiKeyAuthenticator constructor.
     *
     * @param string $universalApiKey
     */
    public function __construct(private string $universalApiKey)
    {
    }

    /**
     * @param Request                      $request
     * @param AuthenticationException|null $authException
     *
     * @return JsonResponse
     */
    public function start(Request $request, ?AuthenticationException $authException = NULL): JsonResponse
    {
        $request;
        $authException;
        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has(self::AUTH_HEADER);
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    public function getCredentials(Request $request): ?string
    {
        return $request->headers->get(self::AUTH_HEADER);
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $userProvider;
        if ($credentials === NULL) {
            return NULL;
        }

        $apiUser = new User();
        $apiUser
            ->setEmail('apiUser')
            ->setDeleted(FALSE);

        return $apiUser;
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $user;

        return $credentials === $this->universalApiKey;
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
     * @param string         $providerKey
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        $request;
        $token;
        $providerKey;

        return NULL;
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return FALSE;
    }

}
