<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class AirtableNewRecordConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector
 */
final class AirtableNewRecordConnector extends ConnectorAbstract
{

    public const NAME = 'airtable_new_record';

    /**
     * AirtableNewRecordConnector constructor.
     *
     * @param ApplicationInstallRepository $applicationInstallRepository
     */
    public function __construct(private readonly ApplicationInstallRepository $applicationInstallRepository)
    {
        parent::__construct($this->applicationInstallRepository);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws ConnectorException
     * @throws CurlException
     * @throws CustomNodeException
     * @throws PipesFrameworkException
     * @throws GuzzleException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        /** @var AirtableApplication $app */
        $app = $this->getApplication();
        if (!$app->getValue($applicationInstall, AirtableApplication::BASE_ID)
            || !$app->getValue($applicationInstall, AirtableApplication::TABLE_NAME)) {

            $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, 'Airtable application install not found');

            return $dto;
        }

        $url        = sprintf(
            '%s/%s/%s',
            AirtableApplication::BASE_URL,
            $app->getValue($applicationInstall, AirtableApplication::BASE_ID),
            $app->getValue($applicationInstall, AirtableApplication::TABLE_NAME),
        );
            $return = $this->getSender()->send(
                $app->getRequestDto(
                    $dto,
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    $url,
                    $dto->getData(),
                ),
            );

        $this->evaluateStatusCode($return->getStatusCode(), $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

}
