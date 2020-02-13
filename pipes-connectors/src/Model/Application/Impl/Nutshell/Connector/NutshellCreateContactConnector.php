<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;

/**
 * Class NutshellCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector
 */
final class NutshellCreateContactConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    public const BASE_URL = 'http://app.nutshell.com/api/v1/json';

    /**
     * @var CurlManagerInterface
     */
    private CurlManagerInterface $curlManager;

    /**
     * @var ApplicationInstallRepository
     */
    private $repository;

    /**
     * NutshellCreateContactConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(CurlManagerInterface $curlManager, DocumentManager $dm)
    {
        $this->curlManager = $curlManager;
        $this->repository  = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'nutshell-create-contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws PipesFrameworkException
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);

        $data = Json::decode($dto->getData());

        $data['jsonrpc'] = '2.0';
        $data['method']  = 'newContact';

        $return = $this->curlManager->send(
            $this->getApplication()->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                self::BASE_URL,
                Json::encode($data)
            )
        );

        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

}
