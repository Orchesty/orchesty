<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad;

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
 * Class IDokladApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad
 */
final class IDokladApplication extends OAuth2ApplicationAbstract
{

    public const    BASE_URL  = 'https://api.idoklad.cz/v3';
    public const    AUTH_URL  = 'https://identity.idoklad.cz/server/connect/authorize';
    public const    TOKEN_URL = 'https://identity.idoklad.cz/server/connect/token';

    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'i-doklad';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'iDoklad Application';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'iDoklad Application';
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
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url ?? self::BASE_URL));
        $request->setHeaders(
            [
                'Content-Type'        => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
            ],
        );

        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return Form
     */
    public function getSettingsForm(): Form
    {
        $form = new Form();
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE));

        return $form;
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::AUTH_URL;
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
     *
     * @return string[]
     */
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return ['idoklad_api', 'offline_access'];
    }

}
