<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Model\Form\Form;

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
     * @return Form
     */
    public function getSettingsForm(): Form;

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