<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\ApiGateway\Authenticator\ApiKey;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFrameworkEnterprise\ApiGateway\Authenticator\Auth0\Auth0Authenticator;
use Hanaboso\UserBundle\Document\User;
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
 * Class EnterpriseApiKeyAuthenticator
 *
 * @package Hanaboso\PipesFrameworkEnterprise\ApiGateway\Authenticator\ApiKey
 */
final class EnterpriseApiKeyAuthenticator extends AbstractAuthenticator
{

    private const string AUTH_HEADER   = 'X-Auth';
    private const string AUTHORIZATION = 'Authorization';

    private DocumentManager $dm;

    /**
     * EnterpriseApiKeyAuthenticator constructor.
     *
     * @param Auth0Authenticator     $auth0Authenticator
     * @param DatabaseManagerLocator $dml
     */
    public function __construct(private readonly Auth0Authenticator $auth0Authenticator, DatabaseManagerLocator $dml)
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;
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
                return $this->auth0Authenticator->authenticate($request);
            }

            $token = $this->dm->getRepository(ApiToken::class)->findOneBy(
                [
                    'key'    => $request->headers->get(self::AUTH_HEADER),
                    'scopes' => ApiTokenScopesEnum::APPLICATIONS_ALL->value,
                ],
            );
            if (!$token) {
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

        return new JsonResponse(
            ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED,
        );
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
