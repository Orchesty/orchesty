<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.9.17
 * Time: 8:06
 */

namespace Hanaboso\PipesFramework\Authorization\Provider;

use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2DtoInterface;

/**
 * Interface OAuth2ProviderInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider
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