<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;

/**
 * Class NullOAuth2Application
 *
 * @package PipesPhpSdkTests\Integration\Command
 */
final class NullOAuth2Application extends OAuth2ApplicationAbstract
{

    /**
     * NullOAuth2Application constructor.
     *
     * @param OAuth2Provider $provider
     */
    public function __construct(OAuth2Provider $provider)
    {
        parent::__construct($provider);
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::BASIC;
    }

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null2';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'null2';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'This is null ouath2 app.';
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto
    (
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $applicationInstall;
        $data;
        $url;

        return new RequestDto(new Uri(''), $method, $dto);
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $field1 = new Field(Field::TEXT, 'settings1', 'Client 11');
        $field2 = new Field(Field::TEXT, 'settings2', 'Client 22');
        $field3 = new Field(Field::PASSWORD, 'settings3', 'Client 33');

        $form = new Form('nullApp', 'nullPublicName');
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        $formStack = new FormStack();

        return $formStack->addForm($form);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        $applicationInstall;

        return TRUE;
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return 'auth/ouath2/url.com';
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return 'token/ouath2/url.com';
    }

}
