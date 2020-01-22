<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;

/**
 * Class TestOAuth2NullApplication
 *
 * @package PipesPhpSdkTests\Integration\Application
 */
class TestOAuth2NullApplication extends OAuth2ApplicationAbstract
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
    public function getKey(): string
    {
        return 'null2';
    }

    /**
     * @return string
     */
    public function getName(): string
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
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto
    {
        $applicationInstall;

        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders(
            [
                'Content-Type' => 'application/vnd.shoptet.v1.0',
                'Accept'       => 'application/json',
            ]
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {
        $form = new Form();

        return $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE));
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
