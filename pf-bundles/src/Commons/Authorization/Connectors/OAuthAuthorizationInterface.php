<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Authorization\Connectors;

/**
 * Interface OAuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\Connectors
 */
interface OAuthAuthorizationInterface
{

    /**
     *
     */
    public function authorize(): void;

    /**
     * @param string[] $data
     */
    public function saveToken(array $data): void;

}
