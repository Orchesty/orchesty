<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class NutshellCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector
 */
final class NutshellCreateContactConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * NutshellCreateContactConnector constructor.
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
     * @throws JsonException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);

        $data = Json::decode($dto->getData());

        $data['jsonrpc'] = '2.0';
        $data['method']  = 'newContact';

        $return = $this->curlManager->send(
            $this->getApplication()->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                NutshellApplication::BASE_URL,
                Json::encode($data),
            )->setDebugInfo($dto),
        );

        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

}
