<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class FakturoidAbstractConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector
 */
abstract class FakturoidAbstractConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    protected const NAME     = '';
    protected const ENDPOINT = '';
    protected const METHOD   = '';

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * FakturoidAbstractConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(private CurlManagerInterface $curlManager, DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
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
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);

        /** @var FakturoidApplication $app */
        $app = $this->getApplication();
        if (!$app->isAuthorized($applicationInstall)) {

            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);

            return $dto;
        }

        $url = sprintf(
            '%s/%s/%s/%s',
            FakturoidApplication::BASE_URL,
            FakturoidApplication::BASE_ACCOUNTS,
            $applicationInstall->getSettings()[ApplicationAbstract::FORM][FakturoidApplication::ACCOUNT],
            static::ENDPOINT,
        );

        $body = NULL;

        $arrayBodyMethods = [CurlManager::METHOD_POST, CurlManager::METHOD_PUT, CurlManager::METHOD_PATCH];

        if (in_array(static::METHOD, $arrayBodyMethods, TRUE)) {
            $body = $dto->getData();
        }

        $return = $this->curlManager->send(
            $app->getRequestDto(
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
