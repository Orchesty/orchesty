<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;

/**
 * Class NullOauthApplication
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
final class NullOauthApplication extends OAuth1ApplicationAbstract
{

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Test';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'this is test app';
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
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $applicationInstall;
        $data;

        return new RequestDto(new Uri((string) $url), $method, $dto);
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form('null','TestNullApp');
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE));

        $formStack = new FormStack();

        return $formStack->addForm($form);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function authorize(ApplicationInstall $applicationInstall): string
    {
        $applicationInstall;

        return 'authorize/url';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $token
     *
     * @return OAuth1ApplicationInterface
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token,
    ): OAuth1ApplicationInterface
    {
        $applicationInstall;
        $token;

        return $this;
    }

    /**
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'token/url';
    }

    /**
     * @return string
     */
    protected function getAuthorizeUrl(): string
    {
        return 'auth/url';
    }

    /**
     * @return string
     */
    protected function getAccessTokenUrl(): string
    {
        return 'accessToken/url';
    }

}
