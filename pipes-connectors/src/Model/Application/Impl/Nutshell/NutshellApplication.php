<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;

/**
 * Class NutshellApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell
 */
final class NutshellApplication extends BasicApplicationAbstract
{

    public const BASE_URL = 'http://app.nutshell.com/api/v1/json';

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
        return 'nutshell';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Nutshell';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Nutshell v1';
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
        ?string $data = NULL
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Basic %s', $this->getToken($applicationInstall)),
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
        return (new Form())
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::USER, 'Username', NULL, TRUE))
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::PASSWORD, 'API Key', NULL, TRUE));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getToken(ApplicationInstall $applicationInstall): string
    {
        return base64_encode(
            sprintf(
                '%s:%s',
                $applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationAbstract::USER],
                $applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationAbstract::PASSWORD]
            )
        );
    }

}
