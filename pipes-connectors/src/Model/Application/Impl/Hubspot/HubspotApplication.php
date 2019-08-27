<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

/**
 * Class HubspotApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot
 */
class HubspotApplication extends OAuth2ApplicationAbstract
{

    public const  BASE_URL    = 'https://api.hubapi.com';
    public const  HUBSPOT_URL = 'https://app.hubspot.com/oauth/authorize';
    public const  TOKEN_URL   = 'https://api.hubapi.com/oauth/v1/token';
    private const SCOPES      = ['contacts'];

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
        return 'hubspot';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Hubspot';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Hubspot v1';
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
        ?string $url,
        ?string $data
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders([
            'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
        ]);
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
        $form        = new Form();
        $field       = new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE);
        $fieldSecret = new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE);
        $form->addField($field);
        $form->addField($fieldSecret);

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

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::HUBSPOT_URL;
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
     * @param array              $scopes
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
        parent::authorize($applicationInstall, self::SCOPES, ScopeFormatter::SPACE);
    }

}
