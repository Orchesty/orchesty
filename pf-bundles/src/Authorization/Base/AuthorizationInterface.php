<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Base;

/**
 * Interface AuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Base
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
     * @param string $method
     * @param string $url
     *
     * @return array
     */
    public function getHeaders(string $method, string $url): array;

    /**
     * @param string $hostname
     *
     * @return string []
     */
    public function getInfo(string $hostname): array;

    /**
     * @return array
     */
    public function getSettings(): array;

    /**
     * @param string[] $data
     */
    public function saveSettings(array $data): void;

    /**
     * @return string
     */
    public function getReadMe(): string;

}
