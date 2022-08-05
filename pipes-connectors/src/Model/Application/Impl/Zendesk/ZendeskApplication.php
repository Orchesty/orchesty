<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

/**
 * Class ZendeskApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Zendesk
 */
final class ZendeskApplication extends OAuth2ApplicationAbstract
{

    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;

    private const AUTH_URL  = 'https://%s.zendesk.com/oauth/authorizations/new';
    private const TOKEN_URL = 'https://%s.zendesk.com/oauth/tokens';
    private const SUBDOMAIN = 'subdomain';
    private const SCOPES    = ['read', 'write'];

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'zendesk';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Zendesk';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Zendesk is a customer support software. It helps companies and organisations manage customer queries and problems through a ticketing system.';
    }

    /**
     * @param ProcessDtoAbstract $dto
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
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
            ],
        );

        if (!empty($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField((new Field(Field::TEXT, self::SUBDOMAIN, 'Subdomain', NULL, TRUE)))
            ->addField((new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE)))
            ->addField(
                (new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE)),
            );

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getAuthUrlWithSubdomain(ApplicationInstall $applicationInstall): string
    {
        return sprintf(
            self::AUTH_URL,
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::SUBDOMAIN],
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getTokenUrlWithSubdomain(ApplicationInstall $applicationInstall): string
    {
        return sprintf(
            self::TOKEN_URL,
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::SUBDOMAIN],
        );
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return '';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string|null        $redirectUrl
     *
     * @return OAuth2Dto
     */
    protected function createDto(ApplicationInstall $applicationInstall, ?string $redirectUrl = NULL): OAuth2Dto
    {
        $dto = new OAuth2Dto(
            $applicationInstall,
            $this->getAuthUrlWithSubdomain($applicationInstall),
            $this->getTokenUrlWithSubdomain($applicationInstall),
        );
        $dto->setCustomAppDependencies($applicationInstall->getUser(), $applicationInstall->getKey());

        if ($redirectUrl) {
            $dto->setRedirectUrl($redirectUrl);
        }

        return $dto;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string[]
     */
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return self::SCOPES;
    }

}
