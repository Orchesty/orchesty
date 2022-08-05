<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class FakturoidAbstractConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector
 */
abstract class FakturoidAbstractConnector extends ConnectorAbstract
{

    protected const NAME     = '';
    protected const ENDPOINT = '';
    protected const METHOD   = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        /** @var FakturoidApplication $app */
        $app = $this->getApplication();
        if (!$app->isAuthorized($applicationInstall)) {

            $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, 'Application not authorized');

            return $dto;
        }

        $url = sprintf(
            '%s/%s/%s/%s',
            FakturoidApplication::BASE_URL,
            FakturoidApplication::BASE_ACCOUNTS,
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][FakturoidApplication::ACCOUNT],
            static::ENDPOINT,
        );

        $body = NULL;

        $arrayBodyMethods = [CurlManager::METHOD_POST, CurlManager::METHOD_PUT, CurlManager::METHOD_PATCH];

        if (in_array(static::METHOD, $arrayBodyMethods, TRUE)) {
            $body = $dto->getData();
        }

        $return = $this->getSender()->send(
            $app->getRequestDto(
                $dto,
                $applicationInstall,
                static::METHOD,
                $url,
                $body,
            ),
        );

        $this->evaluateStatusCode($return->getStatusCode(), $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

}
