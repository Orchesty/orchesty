<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid;

use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
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
    public function getKey(): string
    {
        return 'fakturoid';
    }

    /**
     * @return string
     */
    public function getName(): string
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
        ?string $data = NULL,
    ): RequestDto
    {
        $userName = $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER];
        $password = $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD];

        $request = new RequestDto($method, $this->getUri($url ?? self::BASE_URL));
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Authorization' => sprintf(
                    'Basic %s',
                    Base64::base64UrlEncode(sprintf('%s:%s', $userName, $password)),
                ),
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
        $settings = $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS];

        return isset($applicationInstall->getSettings()[ApplicationAbstract::FORM][self::ACCOUNT])
            && isset($settings[BasicApplicationInterface::USER])
            && isset($settings[BasicApplicationInterface::PASSWORD]);
    }

    /**
     * @return Form
     */
    public function getSettingsForm(): Form
    {
        $form = new Form();
        $form
            ->addField(new Field(Field::TEXT, self::ACCOUNT, 'Account', NULL, TRUE))
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::USER, 'Username', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, BasicApplicationAbstract::PASSWORD, 'API key', TRUE));

        return $form;
    }

}
