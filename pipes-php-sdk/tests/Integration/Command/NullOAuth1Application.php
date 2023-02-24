<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1DtoInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;

/**
 * Class NullOAuth1Application
 *
 * @package PipesPhpSdkTests\Integration\Command
 */
final class NullOAuth1Application extends OAuth1ApplicationAbstract implements OAuth1DtoInterface
{

    /**
     * NullOAuth1Application constructor.
     *
     * @param OAuth1Provider $provider
     */
    public function __construct(OAuth1Provider $provider)
    {
        parent::__construct($provider);
    }

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null1';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'null1';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'This is null ouath1 app.';
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
        $url;
        $data;

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

        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'testPublicName');
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        $formStack = new FormStack();

        return $formStack->addForm($form);
    }

    /**
     * @return string
     */
    public function getConsumerKey(): string
    {
        return 'consumerKey';
    }

    /**
     * @return string
     */
    public function getConsumerSecret(): string
    {
        return 'consumerSecret';
    }

    /**
     * @return string
     */
    public function getSignatureMethod(): string
    {
        return 'signatureMethod';
    }

    /**
     * @return int
     */
    public function getAuthType(): int
    {
        return 1;
    }

    /**
     * @return mixed[]
     */
    public function getToken(): array
    {
        return ['token' => 'Grrrrr'];
    }

    /**
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return 'token/ouath1/url.com';
    }

    /**
     * @return string
     */
    protected function getAuthorizeUrl(): string
    {
        return 'auth/ouath2/url.com';
    }

    /**
     * @return string
     */
    protected function getAccessTokenUrl(): string
    {
        return 'access/token/url.com';
    }

}
