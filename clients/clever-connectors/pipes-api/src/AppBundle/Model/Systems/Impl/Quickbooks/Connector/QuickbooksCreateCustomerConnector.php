<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreateCustomerMapper;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class QuickbooksCreateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksCreateCustomerConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const SUB_URL = '/customer';

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
     * QuickbooksCreateCustomerConnector constructor.
     *
     * @param DocumentManager      $dm
     * @param QuickbooksSystem     $system
     * @param CurlManagerInterface $curl
     */
    public function __construct(
        DocumentManager $dm,
        QuickbooksSystem $system,
        CurlManagerInterface $curl
    )
    {
        $this->curl                    = $curl;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-create-customer-connector';
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
            'Pipedrive has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!$data[QuickbooksCreateCustomerMapper::SUCCESS]) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
                ->setBody($data['body'])
                ->setUri(new Uri(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL));

            try {
                try {
                    $res = $this->curl->send($requestDto);
                } catch (CurlException $exception) {
                    $response = $exception->getResponse();
                    if ($response->getStatusCode() == 500 || $response->getStatusCode() == 401) {
                        $this->logError($response->getStatusCode(), $this->system, $systemInstall);
                    }
                    throw $exception;
                }

                $data[QuickbooksCreateCustomerMapper::SUCCESS] = TRUE;
                $data['body']                                  = $res->getBody();
            } catch (CurlException $e) {
                if ($data[QuickbooksCreateCustomerMapper::ATTEMPT]) {
                    $this->connectorError($e, $this->system, $systemInstall, $dto);
                }
            }

            $data[QuickbooksCreateCustomerMapper::ATTEMPT] = TRUE;
            $dto->setData(json_encode($data));
        }

        return $dto;
    }

}