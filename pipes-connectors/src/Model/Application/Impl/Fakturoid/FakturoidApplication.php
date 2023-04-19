<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\String\Base64;

/**
 * Class FakturoidApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid
 */
final class FakturoidApplication extends BasicApplicationAbstract
{

    public const BASE_URL      = 'https://app.fakturoid.cz/api/v2';
    public const BASE_ACCOUNTS = 'accounts';
    public const ACCOUNT       = 'account';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fakturoid';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Fakturoid aplication';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Fakturoid aplication';
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
        $request  = new RequestDto($this->getUri($url ?? self::BASE_URL), $method, $dto);
        $userName = $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER];
        $password = $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD];

        $request->setHeaders(
            [
                'Authorization' => sprintf(
                    'Basic %s',
                    Base64::base64UrlEncode(sprintf('%s:%s', $userName, $password)),
                ),
                'Content-Type'  => 'application/json',
            ],
        );

        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        $settings = $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM] ?? [];

        return isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::ACCOUNT])
            && isset($settings[BasicApplicationInterface::USER])
            && isset($settings[BasicApplicationInterface::PASSWORD]);
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, self::ACCOUNT, 'Account', NULL, TRUE))
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::USER, 'Username', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, BasicApplicationAbstract::PASSWORD, 'API key', TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

}
