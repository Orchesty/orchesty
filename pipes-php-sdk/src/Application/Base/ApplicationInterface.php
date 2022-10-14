<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Base;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;

/**
 * Interface ApplicationInterface
 *
 * @package Hanaboso\PipesPhpSdk\Application\Base
 */
interface ApplicationInterface
{

    public const  AUTHORIZATION_FORM    = 'authorization_form';
    public const  LIMITER_FORM          = 'limiterForm';
    public const  TOKEN                 = 'token';
    public const  FIELDS                = 'fields';
    public const  FRONTEND_REDIRECT_URL = 'frontend_redirect_url';
    public const  OAUTH_REDIRECT_URL    = 'redirect_url';

    public const  USE_LIMIT   = 'useLimit';
    public const  VALUE       = 'value';
    public const  TIME        = 'time';
    public const  GROUP_VALUE = 'groupValue';
    public const  GROUP_TIME  = 'groupTime';

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
    public function getName(): string;

    /**
     * @return string
     */
    public function getPublicName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return string|null
     */
    public function getLogo(): ?string;

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     */
    public function getRequestDto
    (
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto;

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $settings
     *
     * @return ApplicationInstall
     */
    public function saveApplicationForms(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed[]
     */
    public function getApplicationForms(ApplicationInstall $applicationInstall): array;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $formKey
     * @param string             $fieldKey
     * @param string             $password
     *
     * @return ApplicationInstall
     */
    public function savePassword(
        ApplicationInstall $applicationInstall,
        string $formKey,
        string $fieldKey,
        string $password,
    ): ApplicationInstall;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool;

    /**
     * @return mixed[]
     */
    public function toArray(): array;

}
