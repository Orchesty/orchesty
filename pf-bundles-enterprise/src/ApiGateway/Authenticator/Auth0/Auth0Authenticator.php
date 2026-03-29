<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\ApiGateway\Authenticator\Auth0;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Security\JWTAuthenticator;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
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
 * Dual-mode authenticator: Auth0 RS256 tokens when AUTH0_DOMAIN is set,
 * falls back to legacy HS512 JWTAuthenticator otherwise.
 * Must be the ONLY custom authenticator in the firewall to prevent
 * Symfony's authenticator loop from overriding a successful Auth0 auth
 * with a failed JWT auth.
 */
final class Auth0Authenticator extends AbstractAuthenticator
{

    private const string AUTHORIZATION = 'Authorization';

    /**
     * @var DocumentRepository<User>
     */
    private DocumentRepository $userRepository;

    public function __construct(
        private readonly string $auth0Domain,
        private readonly string $auth0Audience,
        private readonly JwksCacheService $jwksCacheService,
        private readonly JWTAuthenticator $jwtAuthenticator,
        DatabaseManagerLocator $dml,
        ResourceProvider $resourceProvider,
    )
    {
        /** @phpstan-var class-string<User> $userClass */
        $userClass            = $resourceProvider->getResource(ResourceEnum::USER);
        $this->userRepository = $dml->get()->getRepository($userClass);
    }

    public function supports(Request $request): ?bool
    {
        return TRUE;
    }

    public function authenticate(Request $request): Passport
    {
        if ($this->auth0Domain !== '') {
            $jwt = $this->extractToken($request);
            if ($jwt) {
                $header = $this->parseJwtHeader($jwt);
                if (($header['alg'] ?? '') === 'RS256' && isset($header['kid'])) {
                    return $this->authenticateAuth0($jwt, $header);
                }
            }
        }

        return $this->jwtAuthenticator->authenticate($request);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return NULL;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(
            [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],
            Response::HTTP_UNAUTHORIZED,
        );
    }

    private function authenticateAuth0(string $jwt, array $header): Passport
    {
        try {
            $kid = $header['kid'] ?? '';
            $jwk = $this->jwksCacheService->getSigningKey($kid);

            $algorithmManager = new AlgorithmManager([new RS256()]);
            $verifier         = new JWSVerifier($algorithmManager);
            $serializer       = new CompactSerializer();

            $jws = $serializer->unserialize($jwt);

            if (!$verifier->verifyWithKey($jws, $jwk, 0)) {
                throw new AuthenticationException('Invalid Auth0 token signature');
            }

            /** @var string $payload */
            $payload = $jws->getPayload();
            $claims  = Json::decode($payload);

            $this->validateClaims($claims);

            $email = $claims['email'] ?? NULL;
            if (!$email) {
                $email = $this->fetchEmailFromUserInfo($jwt);
            }
            if (!$email) {
                throw new AuthenticationException('Auth0 token missing email claim');
            }

            return new SelfValidatingPassport(
                new UserBadge(
                    $email,
                    function (string $email): User {
                        /** @var User|null $user */
                        $user = $this->userRepository->findOneBy([
                            'deleted' => FALSE,
                            'email'   => $email,
                        ]);

                        if (!$user) {
                            throw new AuthenticationException(
                                sprintf('User [%s] not found', $email),
                            );
                        }

                        return clone $user;
                    },
                ),
            );
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (Throwable $t) {
            throw new AuthenticationException('Auth0 authentication failed', $t->getCode(), $t);
        }
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->headers->get(self::AUTHORIZATION)
            ?? $request->query->get(self::AUTHORIZATION);

        if (!$header) {
            return NULL;
        }

        return str_replace('Bearer ', '', $header);
    }

    /**
     * @return mixed[]
     */
    private function parseJwtHeader(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return [];
        }

        $decoded = base64_decode(strtr($parts[0], '-_', '+/'), TRUE);
        if ($decoded === FALSE) {
            return [];
        }

        try {
            return Json::decode($decoded);
        } catch (Throwable) {
            return [];
        }
    }

    private function fetchEmailFromUserInfo(string $accessToken): ?string
    {
        $url  = sprintf('https://%s/userinfo', $this->auth0Domain);
        $ctx  = stream_context_create([
            'http' => [
                'header'  => sprintf("Authorization: Bearer %s\r\n", $accessToken),
                'timeout' => 5,
            ],
        ]);
        $json = @file_get_contents($url, FALSE, $ctx);

        if ($json === FALSE) {
            return NULL;
        }

        try {
            $data = Json::decode($json);

            return $data['email'] ?? NULL;
        } catch (Throwable) {
            return NULL;
        }
    }

    private function validateClaims(array $claims): void
    {
        $now = DateTimeUtils::getUtcDateTime()->getTimestamp();

        if (isset($claims['exp']) && $claims['exp'] < $now) {
            throw new AuthenticationException('Auth0 token expired');
        }

        $expectedIssuer = sprintf('https://%s/', $this->auth0Domain);
        if (isset($claims['iss']) && $claims['iss'] !== $expectedIssuer) {
            throw new AuthenticationException(
                sprintf('Invalid issuer: expected [%s], got [%s]', $expectedIssuer, $claims['iss']),
            );
        }

        if ($this->auth0Audience !== '' && isset($claims['aud'])) {
            $audiences = is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']];
            if (!in_array($this->auth0Audience, $audiences, TRUE)) {
                throw new AuthenticationException('Auth0 token audience mismatch');
            }
        }
    }

}
