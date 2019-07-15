<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2DtoInterface;

/**
 * Interface OAuth2ProviderInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
interface OAuth2ProviderInterface
{

    /**
     * @param OAuth2DtoInterface $dto
     * @param array              $scopes
     *
     * @throws AuthorizationException
     */
    public function authorize(OAuth2DtoInterface $dto, array $scopes = []): void;

    /**
     * @param OAuth2DtoInterface $dto
     * @param array              $request
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getAccessToken(OAuth2DtoInterface $dto, array $request): array;

    /**
     * @param OAuth2DtoInterface $dto
     * @param array              $token
     *
     * @return array
     * @throws AuthorizationException
     */
    public function refreshAccessToken(OAuth2DtoInterface $dto, array $token): array;

}
