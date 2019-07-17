<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;

/**
 * Class MailchimpApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp
 */
class MailchimpApplication extends OAuth2ApplicationAbstract
{

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    public const MAILCHIMP_URL            = 'https://login.mailchimp.com/oauth2/authorize';
    public const MAILCHIMP_DATACENTER_URL = 'https://login.mailchimp.com';
    public const AUDIENCE_ID              = 'audience_id';
    public const TOKEN_URL                = 'https://login.mailchimp.com/oauth2/token';

    /**
     * OAuth2ApplicationAbstract constructor.
     *
     * @param OAuth2Provider       $provider
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(OAuth2Provider $provider, CurlManagerInterface $curlManager)
    {
        parent::__construct($provider);
        $this->curlManager = $curlManager;
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
    public function getKey(): string
    {
        return 'mailchimp';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mailchimp';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Mailchimp v3';
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
            'Authorization' => sprintf('OAuth %s', $this->getAccessToken($applicationInstall)),
            'Accept'        => 'application/json',
        ]);

        if (!empty($data)) {
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
        $form          = new Form();
        $field         = new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE);
        $fieldSecret   = new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE);
        $fieldAudience = new Field(Field::TEXT, self::AUDIENCE_ID, 'Audience Id', NULL, TRUE);
        $form->addField($field);
        $form->addField($fieldSecret);
        $form->addField($fieldAudience);

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
        return self::MAILCHIMP_URL;
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
     * @param array              $token
     *
     * @return OAuth2ApplicationInterface
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws AuthorizationException
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token
    ): OAuth2ApplicationInterface
    {
        parent::setAuthorizationToken($applicationInstall, $token);

        $applicationInstall->setSettings([
            OAuth2ApplicationInterface::API_KEYPOINT => $this->getApiEndpoint($applicationInstall),
        ]);

        return $this;

    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getApiEndpoint(ApplicationInstall $applicationInstall): string
    {

        $return = $this->curlManager->send($this->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_GET,
            sprintf('%s/oauth2/metadata', self::MAILCHIMP_DATACENTER_URL),
            ''
        ));

        return $return->getJsonBody()['api_endpoint'];
    }

}

