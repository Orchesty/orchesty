<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class AirtableNewRecordConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\Connector
 */
final class AirtableNewRecordConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var ApplicationInstallRepository&ObjectRepository<ApplicationInstall>
     */
    private $repository;

    /**
     * AirtableNewRecordConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(
        CurlManagerInterface $curlManager,
        DocumentManager $dm
    )
    {
        $this->curlManager = $curlManager;
        $this->repository  = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'airtable_new_record';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws PipesFrameworkException
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);

        /** @var AirtableApplication $app */
        $app = $this->application;
        if (!$app->getValue($applicationInstall, AirtableApplication::BASE_ID)
            || !$app->getValue($applicationInstall, AirtableApplication::TABLE_NAME)) {

            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);

            return $dto;
        }

        $url    = sprintf(
            '%s/%s/%s',
            AirtableApplication::BASE_URL,
            $app->getValue($applicationInstall, AirtableApplication::BASE_ID),
            $app->getValue($applicationInstall, AirtableApplication::TABLE_NAME)
        );
        $return = $this->curlManager->send(
            $this->application->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                $url,
                $dto->getData()
            )
        );

        $this->evaluateStatusCode($return->getStatusCode(), $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

}
