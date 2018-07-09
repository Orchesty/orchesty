<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SyncConnector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class SyncResultConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SyncConnector
 */
final class SyncResultConnector implements ConnectorInterface
{

    private const PATH = '/api/synchronization/saveResult';

    /**
     * @var CurlManagerInterface
     */
    private $curl;
    /**
     * @var string
     */
    private $host;

    /**
     * @param CurlManagerInterface $curl
     * @param string               $host
     */
    public function __construct(CurlManagerInterface $curl, string $host)
    {
        $this->curl = $curl;
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'sync-result-connector';
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
            'Sync Result Connector does not support Events!',
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
        $request = new RequestDto(CurlManager::METHOD_POST, new Uri($this->host . self::PATH));
        $request->setBody($dto->getData());
        $request->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        try {
            $response = $this->curl->send($request);
            $this->validateResponse($response);
        } catch (CurlException $e) {
            throw new ConnectorException("SyncResultConnector failed.", $e->getCode(), $e);
        }

        return $dto;
    }

    /**
     * @param ResponseDto $response
     *
     * @throws ConnectorException
     */
    private function validateResponse(ResponseDto $response): void
    {
        if ($response->getStatusCode() !== 200) {
            $responseBody = json_decode($response->getBody(), TRUE);
            throw new ConnectorException(
                sprintf(
                    'SyncResult connector failed [statusCode="%s", message="%s"]',
                    $response->getStatusCode(),
                    $responseBody['message'] ?? ''
                ),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }
    }

}
