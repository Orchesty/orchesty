<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;

/**
 * Class TestOAuth2NullApplication
 *
 * @package PipesPhpSdkTests\Integration\Application
 */
final class TestOAuth2NullApplication extends OAuth2ApplicationAbstract
{

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
        return 'Null2';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Application for test purposes';
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

        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/vnd.shoptet.v1.0',
            ],
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'testPublicName');
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE));

        $formStack = new FormStack();

        return $formStack->addForm($form);
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return 'oauth/url';
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return 'token/url';
    }

}
