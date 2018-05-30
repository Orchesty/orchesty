<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.5.18
 * Time: 16:22
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppMapperAbstract;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class SalesforceAppMapFieldsConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
class SalesforceAppMapFieldsConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    public const MAP_FIELDS = 'mapFields';

    protected const SYNC_STATE_URL = '%s/services/apexrest/CMHB/pipes/mapfields';

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SalesforceAppSystem
     */
    private $system;

    /**
     * SalesforceAuthConnector constructor.
     *
     * @param CurlManager         $curl
     * @param DocumentManager     $dm
     * @param SalesforceAppSystem $system
     */
    public function __construct(CurlManager $curl, DocumentManager $dm, SalesforceAppSystem $system)
    {
        $this->curl                    = $curl;
        $this->dm                      = $dm;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hbpf.connector.salesforce_app-map_fields-connector';
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
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $response = $this->curl->send($requestDto);

        if ($response->getStatusCode() !== 200) {
            $this->logError($response->getStatusCode(), $this->system, $systemInstall);

            if (Strings::contains($response->getBody(), 'REQUEST_LIMIT_EXCEEDED')) {
                return HeadersUtils::setLimitHeaderToDto($dto);
            }

            return HeadersUtils::setStopHeaderToDto($dto);
        }

        $data = $this->parseData($response->getBody());

        $settings                   = $systemInstall->getSettings();
        $settings[self::MAP_FIELDS] = $data;
        $systemInstall->setSettings($settings);

        $this->dm->flush();

        return $dto;
    }

    /**
     * @param string $response
     *
     * @return array
     */
    private function parseData(string $response): array
    {
        $data = json_decode($response, TRUE);
        $res  = [];

        foreach ($data as $item) {
            $res[] = [
                SalesforceAppMapperAbstract::CM_FIELD  => $item[SalesforceAppMapperAbstract::CM_FIELD] ?? '',
                SalesforceAppMapperAbstract::ID_CUSTOM => $item[SalesforceAppMapperAbstract::ID_CUSTOM] ?? '',
            ];
        }

        return $res;
    }

}