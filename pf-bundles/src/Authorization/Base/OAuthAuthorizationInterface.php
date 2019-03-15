<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Base;

/**
 * Interface OAuthAuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Base
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
