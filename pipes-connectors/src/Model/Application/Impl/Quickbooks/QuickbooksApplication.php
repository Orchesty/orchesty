<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks;

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

/**
 * Class QuickbooksApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks
 */
final class QuickbooksApplication extends OAuth2ApplicationAbstract
{

    public const  QUICKBOOKS_URL = 'https://appcenter.intuit.com/connect/oauth2';
    public const  TOKEN_URL      = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    public const  APP_ID         = 'app_id';

    private const SCOPES   = ['com.intuit.quickbooks.accounting'];
    private const VERSION  = 'v3';
    private const BASE_URL = 'https://quickbooks.api.intuit.com';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'quickbooks';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
        $request = new RequestDto(
            $this->getUri(sprintf('%s%s', $this->getBaseUrl($applicationInstall), $url)),
            $method,
            $dto,
        );
        $request->setHeaders(
            [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
                'Content-Type'  => 'application/json',
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
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::APP_ID, 'Realm Id', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
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

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getBaseUrl(ApplicationInstall $applicationInstall): string
    {
        return sprintf(
            '%s/%s/company/%s',
            self::BASE_URL,
            self::VERSION,
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::APP_ID],
        );
    }

}
