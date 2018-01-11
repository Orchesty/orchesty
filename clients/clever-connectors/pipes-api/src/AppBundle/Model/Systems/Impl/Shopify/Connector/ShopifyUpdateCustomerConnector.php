<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class ShopifyUpdateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifyUpdateCustomerConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const SUB_URL = '/admin/customers/%s.json';

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var ShopifySystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * ShopifyUpdateCustomerConnector constructor.
     *
     * @param ShopifySystem        $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     */
    function __construct(ShopifySystem $system, DocumentManager $dm, CurlManagerInterface $curl)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->curl                    = $curl;
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-update-customer-connector';
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
            'ProcessEvent is not implemented, Shopify updateCustomerConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $data          = json_decode($dto->getData(), TRUE);

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_PUT);
        $uri        = new Uri(sprintf(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL, $data['id']));

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri($uri)
            ->setBody($data['body']);

        try {
            $res = $this->curl->send($requestDto);
        } catch (CurlException $exception) {
            if ($exception->getResponse()) {
                $this->logError($exception->getResponse()->getStatusCode(), $this->system, $systemInstall);
            }

            if (Strings::contains($exception->getMessage(), '422 Unprocessable Entity')) {
                // ShopifyUpdateCustomerConnector: custom field already exists and is set
                return $dto;
            }

            throw $exception;
        }

        $data = json_decode($res->getBody(), TRUE);

        if (!array_key_exists('customer', $data)) {
            throw new CleverConnectorsException(
                'CM field does not exist, Shopify updateCustomerConnector.',
                CleverConnectorsException::MISSING_DATA
            );
        } else if ($res->getStatusCode() !== 200) {
            throw new CleverConnectorsException(
                'Failed to update customer - unknown error, Shopify updateCustomerConnector.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(json_encode($data['customer']));
    }

}