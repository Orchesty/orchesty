<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 17.8.17
 * Time: 13:52
 */

namespace Hanaboso\PipesFramework\Authorizations\Provider;

use Hanaboso\PipesFramework\Authorizations\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorizations\Provider\Dto\OAuth1Dto;

/**
 * Interface ProviderInterface
 *
 * @package Hanaboso\PipesFramework\Authorizations\Provider
 */
interface ProviderInterface
{

    /**
     * @param OAuth1Dto $dto
     * @param string    $tokenUrl
     * @param string    $authorizeUrl
     * @param array     $scopes
     *
     * @throws AuthorizationException
     */
    public function authorize(OAuth1Dto $dto, string $tokenUrl, string $authorizeUrl, array $scopes = []): void;

    /**
     * @param OAuth1Dto $dto
     * @param array     $request
     * @param string    $accessTokenUrl
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getAccessToken(OAuth1Dto $dto, array $request, string $accessTokenUrl): array;

    /**
     * @param OAuth1Dto $dto
     * @param string    $method
     * @param string    $url
     *
     * @return string
     * @throws AuthorizationException
     */
    public function getAuthorizeHeader(OAuth1Dto $dto, string $method, string $url): string;

}