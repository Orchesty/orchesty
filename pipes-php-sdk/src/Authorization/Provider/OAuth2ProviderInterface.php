<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2DtoInterface;

/**
 * Interface OAuth2ProviderInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
interface OAuth2ProviderInterface extends OAuthProviderInterface
{

    /**
     * @param OAuth2DtoInterface $dto
     * @param string[]           $scopes
     *
     * @return string
     */
    public function authorize(OAuth2DtoInterface $dto, array $scopes = []): string;

    /**
     * @param OAuth2DtoInterface $dto
     * @param mixed[]            $request
     *
     * @return mixed[]
     * @throws AuthorizationException
     */
    public function getAccessToken(OAuth2DtoInterface $dto, array $request): array;

    /**
     * @param OAuth2DtoInterface $dto
     * @param mixed[]            $token
     *
     * @return mixed[]
     * @throws AuthorizationException
     */
    public function refreshAccessToken(OAuth2DtoInterface $dto, array $token): array;

}
