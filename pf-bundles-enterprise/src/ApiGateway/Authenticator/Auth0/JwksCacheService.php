<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\ApiGateway\Authenticator\Auth0;

use Hanaboso\Utils\File\File;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class JwksCacheService
 *
 * @package Hanaboso\PipesFrameworkEnterprise\ApiGateway\Authenticator\Auth0
 */
final class JwksCacheService
{

    private const int CACHE_TTL = 3_600;

    /**
     * JwksCacheService constructor.
     *
     * @param string         $auth0Domain
     * @param CacheInterface $cache
     */
    public function __construct(private readonly string $auth0Domain, private readonly CacheInterface $cache)
    {
    }

    /**
     * @param string $kid
     *
     * @return JWK
     */
    public function getSigningKey(string $kid): JWK
    {
        $jwkSet = $this->getJwkSet();

        if (!$jwkSet->has($kid)) {
            $jwkSet = $this->getJwkSet(TRUE);
        }

        if (!$jwkSet->has($kid)) {
            throw new RuntimeException(sprintf('Key with kid [%s] not found in JWKS', $kid));
        }

        return $jwkSet->get($kid);
    }

    /**
     * @param bool $forceRefresh
     *
     * @return JWKSet
     */
    private function getJwkSet(bool $forceRefresh = FALSE): JWKSet
    {
        if ($forceRefresh) {
            $this->cache->delete('auth0_jwks');
        }

        /** @var string $jwksJson */
        $jwksJson = $this->cache->get('auth0_jwks', function (ItemInterface $item): string {
            $item->expiresAfter(self::CACHE_TTL);

            $url = sprintf('https://%s/.well-known/jwks.json', $this->auth0Domain);

            return File::getContent($url);
        });

        return JWKSet::createFromJson($jwksJson);
    }

}
