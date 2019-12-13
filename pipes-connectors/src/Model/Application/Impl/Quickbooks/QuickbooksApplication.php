<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

/**
 * Class QuickbooksApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks
 */
final class QuickbooksApplication extends OAuth2ApplicationAbstract
{

    public const  QUICKBOOKS_URL = 'https://appcenter.intuit.com/connect/oauth2';
    public const  TOKEN_URL      = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    private const SCOPES         = ['com.intuit.quickbooks.accounting'];

    private const APP_ID = 'app_id';

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
    public function getKey(): string
    {
        return 'quickbooks';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Quickbooks';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Quickbooks v1';
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::QUICKBOOKS_URL;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $scopes
     * @param string             $separator
     */
    public function authorize(
        ApplicationInstall $applicationInstall,
        array $scopes = [],
        string $separator = ScopeFormatter::COMMA
    ): void
    {
        $scopes;
        $separator;

        $this->provider->authorize(
            $this->createDto($applicationInstall),
            self::SCOPES,
            ScopeFormatter::SPACE
        );

    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Basic %s', $this->getAccessToken($applicationInstall)),
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
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE))
            ->addField(new Field(Field::TEXT, self::APP_ID, 'Application Id', NULL, TRUE));

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        try {
            $this->getAccessToken($applicationInstall);

            return TRUE;
        } catch (ApplicationInstallException $e) {

            return FALSE;
        }
    }

}
