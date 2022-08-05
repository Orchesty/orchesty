<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;

/**
 * Class AirtableApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable
 */
final class AirtableApplication extends BasicApplicationAbstract
{

    public const  BASE_URL   = 'https://api.airtable.com/v0';
    public const  BASE_ID    = 'base_id';
    public const  TABLE_NAME = 'table_name';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'airtable';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
     * @param ProcessDtoAbstract $dto
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
        $form->addField(new Field(Field::TEXT, BasicApplicationAbstract::TOKEN, 'API Key', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, AirtableApplication::BASE_ID, 'Base id', NULL, TRUE));
        $form->addField(new Field(Field::TEXT, AirtableApplication::TABLE_NAME, 'Table name', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return
            isset(
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN],
            )
            &&
            isset(
                $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][AirtableApplication::BASE_ID],
            )
            &&
            isset(
                $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][AirtableApplication::TABLE_NAME],
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
        if (isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][$value])) {
            return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][$value];
        }

        return NULL;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws AuthorizationException
     */
    private function getAccessToken(ApplicationInstall $applicationInstall): string
    {
        if (isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN])) {
            return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN];
        }

        throw new AuthorizationException(
            'There is no access token',
            AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND,
        );
    }

}
