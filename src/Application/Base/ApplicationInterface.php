<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface ApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application
 */
interface ApplicationInterface
{

    public const  AUTHORIZATION_SETTINGS = 'authorization_settings';
    public const  TOKEN                  = 'token';
    public const  REDIRECT_URL           = 'redirect_url';

    /**
     * @return string
     */
    public function getAuthorizationType(): string;

    /**
     * @return string
     */
    public function getApplicationType(): string;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return bool
     */
    public function isWebhook(): bool;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     */
    public function getRequestDto
    (
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url,
        ?string $data
    ): RequestDto;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return array
     */
    public function getSettingsFields(ApplicationInstall $applicationInstall): array;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return ApplicationInstall
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool;

    /**
     * @return array
     */
    public function toArray(): array;

}