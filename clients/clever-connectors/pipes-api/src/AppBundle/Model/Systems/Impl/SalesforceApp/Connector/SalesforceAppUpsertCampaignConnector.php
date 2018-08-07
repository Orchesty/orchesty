<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.4.18
 * Time: 18:34
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class SalesforceAppUpsertCampaignConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
class SalesforceAppUpsertCampaignConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL = '%s/services/apexrest/CMHB/pipes/campaign_sync';

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * @var SalesforceAppSystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * SalesforceAuthConnector constructor.
     *
     * @param SalesforceAppSystem $system
     * @param CurlManager         $curl
     * @param DocumentManager     $dm
     */
    public function __construct(SalesforceAppSystem $system, CurlManager $curl, DocumentManager $dm)
    {
        $this->curl                    = $curl;
        $this->system                  = $system;
        $this->logger                  = new NullLogger();
        $this->dm                      = $dm;
        $this->systemInstallRepository = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hbpf.connector.salesforce_app-upsert-campaign-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Salesforceapp has no support for Event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        try {
            $request = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
            $uri     = new Uri(sprintf(static::URL, rtrim($request->getUri(TRUE), '/')));
            $request = RequestDto::from($request, $uri);
            $request->setBody($dto->getData());
            $this->curl->send($request);
        } catch (Throwable $t) {
            $this->logError(500, $this->system, $systemInstall);
            if (!Strings::contains($t->getMessage(), 'INVALID_SESSION_ID')) {
                throw new ConnectorException($t->getMessage());
            }
        }

        return $dto;
    }

}