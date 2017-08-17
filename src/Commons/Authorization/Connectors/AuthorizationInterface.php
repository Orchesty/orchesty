<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Authorization\Connectors;

/**
 * Interface AuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\Connectors
 */
interface AuthorizationInterface
{

    public const BASIC  = 'basic';
    public const OAUTH  = 'oauth';
    public const OAUTH2 = 'oauth2';

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getAuthorizationType(): string;

    /**
     * @return bool
     */
    public function isAuthorized(): bool;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return string[]
     */
    public function getInfo(): array;

}
