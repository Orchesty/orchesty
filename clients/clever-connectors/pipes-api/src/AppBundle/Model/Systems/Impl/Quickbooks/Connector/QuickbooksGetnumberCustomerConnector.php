<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreateCustomerMapper;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerInterface;

/**
 * Class QuickbooksGetnumberCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksGetnumberCustomerConnector implements ConnectorInterface
{

    private const SUB_URL = '/query?query=';

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    private $systemInstallRepository;

    /**
     * @var QuickbooksSystem
     */
    private $system;

    /**
     * @var LoggerInterface
     */
    private $notificationLogger;

    /**
     * QuickbooksCreateCustomerConnector constructor.
     *
     * @param DocumentManager      $dm
     * @param QuickbooksSystem     $system
     * @param CurlManagerInterface $curl
     * @param LoggerInterface      $notificationLogger
     */
    public function __construct(
        DocumentManager $dm,
        QuickbooksSystem $system,
        CurlManagerInterface $curl,
        LoggerInterface $notificationLogger
    )
    {
        $this->curl                    = $curl;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->notificationLogger      = $notificationLogger;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-getnumber-customer-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Quickbooks has no support for event, getnumberCustomerConnector',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if ($data[QuickbooksCreateCustomerMapper::SUCCESS]) {
            return $dto;
        }

        $body = json_decode($data['body'], TRUE);

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri(new Uri(rtrim($requestDto->getUri(TRUE), '/') . $this->getQuery($body)));

        $res = $this->curl->send($requestDto);

        if ($res->getStatusCode() == 500) {
            $msgData = [
                'guid'        => $systemInstall->getUser(),
                'token'       => $systemInstall->getToken(),
                'system_key'  => $this->system->getKey(),
                'system_name' => $this->system->getName(),
            ];
            $this->notificationLogger->info(NotificationTypeEnum::SERVICE_UNAVAILABLE, $msgData);
        }

        $resBody = json_decode($res->getBody(), TRUE);

        if ($res->getStatusCode() !== 200
            || !array_key_exists('QueryResponse', $resBody)
            || !array_key_exists('totalCount', $resBody['QueryResponse'])
        ) {
            throw new CleverConnectorsException(
                'Failed to query name index, QuickbooksGetnumberConnector.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }

        $body[QuickbooksCreateCustomerMapper::LAST_NAME] .= '#' . (string) ($resBody['QueryResponse']['totalCount'] + 1);

        $data['body'] = json_encode($body);

        return $dto->setData(json_encode($data));
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function getQuery(array $data): string
    {
        return sprintf(
            '%s%s\'%s\'%s\'%s%s\'',
            self::SUB_URL,
            urlencode('SELECT COUNT(*) FROM CUSTOMER WHERE Active IN (true, false) AND GivenName='),
            $data[QuickbooksCreateCustomerMapper::FIRST_NAME],
            urlencode(' AND FamilyName LIKE '),
            $data[QuickbooksCreateCustomerMapper::LAST_NAME],
            urlencode('#%')
        );
    }

}