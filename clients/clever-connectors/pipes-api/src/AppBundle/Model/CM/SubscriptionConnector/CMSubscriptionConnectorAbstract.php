<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriptionConnector;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:49
 */

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Exceptions\Exception;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CMSubscriptionConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriptionConnector
 */
abstract class CMSubscriptionConnectorAbstract extends CMAuthorization implements ConnectorInterface, LoggerAwareInterface
{

    /**
     * @var CurlManagerInterface
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CMSubscriptionConnectorAbstract constructor.
     *
     * @param CurlManagerInterface $curl
     */
    function __construct(CurlManagerInterface $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'CleverMonitorsSubscription';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws Exception
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new Exception('CMSubscriptionConnector has no support for webhooks!');
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return ConnectorInterface
     */
    public function setLogger(LoggerInterface $logger): ConnectorInterface
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function getUrl(string $email = ''): string
    {
        if ($email != '') {
            $email = '/' . $email;
        }

        return sprintf('https://api.dev.clevermonitor.com/v1.2/subscribers/email%s', $email);
    }

    /**
     * @param ProcessDto $dto
     * @param string $method
     * @param int[] $statusCode
     * @param string $email
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws ConnectorException
     */
    public function processCMAction(ProcessDto $dto, string $method, array $statusCode, string $email = ''): ProcessDto
    {
        if (!isset($dto->getHeaders()['guid']) || !isset($dto->getHeaders()['token'])) {
            throw new CleverConnectorsException(
                'Missing required data in headers.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $req = new RequestDto($method, new Uri($this->getUrl($email)));
        $req->setHeaders($this->getAuthorizationHeaders($dto->getHeaders()['guid'][0], $dto->getHeaders()['token'][0])); // TODO why header array?
        $req->setBody($dto->getData());
        try {
            $res = $this->curl->send($req, [
                RequestOptions::CERT => __DIR__ . '/../../../../../../hanaboso.cert.pem',     // TODO do konfigu
                RequestOptions::SSL_KEY => __DIR__ . '/../../../../../../hanaboso.cert.pem',
                RequestOptions::VERIFY => __DIR__ . '/../../../../../../ca.crt',
            ]);
        } catch (Exception $e) {
            $this->logger->error(sprintf('CM %s subscription failed.', $method), ['exception' => $e]);
            throw new ConnectorException(
                sprintf('%s subscription failed.', $method),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }

        if (!in_array($res->getStatusCode(), $statusCode)) {
            $this->logger->error(sprintf('CM %s subscription failed.', $method), ['exception' => $res->getBody()]);
            throw new ConnectorException(
                sprintf('%s subscription returned [%s] status code.', $method, $res->getStatusCode()),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }

        $data = $res->getBody();
        $data = str_replace(PHP_EOL, '', $data);
        $dto->setData($data);

        return $dto;
    }

}