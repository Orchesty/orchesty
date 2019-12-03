<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;

/**
 * Class AirtableApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable
 */
final class AirtableApplication extends BasicApplicationAbstract
{

    public const  BASE_URL   = 'https://api.airtable.com/v0';
    public const  BASE_ID    = 'BASE_ID';
    public const  TABLE_NAME = 'TABLE_NAME';

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
        return 'airtable';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Airtable';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Airtable v1';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws AuthorizationException
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
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
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
        $form->addField(new Field(Field::TEXT, BasicApplicationAbstract::TOKEN, 'API Key', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, AirtableApplication::BASE_ID, 'Base id', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, AirtableApplication::TABLE_NAME, 'Table name', NULL, TRUE));

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws AuthorizationException
     */
    public function getAccessToken(ApplicationInstall $applicationInstall): string
    {
        $token = $this->getValue($applicationInstall, BasicApplicationInterface::TOKEN);
        if ($token) {
            return $token;
        }

        throw new AuthorizationException(
            'There is no access token',
            AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND
        );

    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $value
     *
     * @return string|null
     */
    public function getValue(ApplicationInstall $applicationInstall, string $value): ?string
    {

        if (isset($applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][$value])) {
            return $applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][$value];
        }

        return NULL;
    }

}
