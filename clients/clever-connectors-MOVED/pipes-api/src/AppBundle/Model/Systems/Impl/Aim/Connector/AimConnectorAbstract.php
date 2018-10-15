<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;

/**
 * Class AimConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector
 */
abstract class AimConnectorAbstract implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const PATH_UPSERT = '/api/synchronization/saveItem';
    private const PATH_DELETE = '/api/synchronization/deleteItem/%s/%s';

    /**
     * @var AimSystem
     */
    private $system;
    /**
     * @var CurlManagerInterface
     */
    private $curl;
    /**
     * @var string
     */
    private $destination;
    /**
     * @var string
     */
    private $host;

    /**
     * @var array
     */
    private $mandatoryFields = [
        'category',
        'sku',
        'time',
    ];

    /**
     * @param AimSystem            $system
     * @param CurlManagerInterface $curl
     * @param string               $destination
     * @param string               $host
     */
    public function __construct(
        AimSystem $system,
        CurlManagerInterface $curl,
        string $destination,
        string $host
    )
    {
        $this->system      = $system;
        $this->curl        = $curl;
        $this->destination = $destination;
        $this->host        = $host;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'aim-' . $this->destination;
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
            'Aim has no support for Events!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $validData = $this->validateInputData($dto);

        $action = $dto->getHeader(CMHeaders::createKey(AimSystem::HEADER_ACTION), NULL);
        switch ($action) {
            case AimSystem::SYNC_ACTION:
                return $this->actionUpsert($dto, $validData);

            case AimSystem::DELETE_ACTION:
                return $this->actionDelete($dto, $validData);

            default:
                throw new ConnectorException(
                    sprintf('Invalid action "%s"', $action),
                    ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
                );
        }
    }

    /**
     * @param ProcessDto $dto
     * @param array      $data
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws CurlException
     */
    private function actionUpsert(ProcessDto $dto, array $data): ProcessDto
    {
        $url = $this->host . self::PATH_UPSERT;

        return $this->runAction($dto, CurlManager::METHOD_POST, $url, $data);
    }

    /**
     * @param ProcessDto $dto
     * @param array      $data
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws CurlException
     */
    private function actionDelete(ProcessDto $dto, array $data): ProcessDto
    {
        $url = $this->host . sprintf(self::PATH_DELETE, $data['category'], $data['sku']);

        return $this->runAction($dto, CurlManager::METHOD_DELETE, $url, $data);
    }

    /**
     * @param ProcessDto $dto
     * @param string     $method
     * @param string     $url
     * @param array      $data
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws CurlException
     */
    private function runAction(ProcessDto $dto, string $method, string $url, array $data): ProcessDto
    {
        $request = new RequestDto($method, new Uri($url));
        $request->setBody($dto->getData());
        $request->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        try {
            $response     = $this->curl->send($request);
            $responseBody = $this->getResponseData($response, $data);
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, new SystemInstall(), $dto);
        }

        return $dto->setData(json_encode($responseBody));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws ConnectorException
     */
    private function validateInputData(ProcessDto $dto): array
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data)) {
            throw new ConnectorException('Invalid data.', ConnectorException::INVALID_SETTING);
        }

        foreach ($this->mandatoryFields as $mf) {
            if (!array_key_exists($mf, $data)) {
                throw new ConnectorException(
                    sprintf('Missing mandatory field "%s"', $mf),
                    ConnectorException::INVALID_SETTING
                );
            }
        }

        return $data;
    }

    /**
     * @param ResponseDto $response
     * @param array       $requestData
     *
     * @return array
     * @throws ConnectorException
     */
    private function getResponseData(ResponseDto $response, array $requestData): array
    {
        $responseData = json_decode($response->getBody(), TRUE);

        if (!is_array($responseData) ||
            !array_key_exists('status', $responseData) ||
            !array_key_exists('message', $responseData)
        ) {
            throw new ConnectorException(
                sprintf('Aim Connector returned invalid response'),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }

        if ($response->getStatusCode() !== 200) {
            throw new ConnectorException(
                sprintf(
                    'SyncResult connector failed [statusCode="%s", message="%s"]',
                    $response->getStatusCode(),
                    $responseBody['message'] ?? ''
                ),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }

        return [
            'status'      => $responseData['status'],
            'message'     => $responseData['message'],
            'category'    => $requestData['category'],
            'sku'         => $requestData['sku'],
            'time'        => $requestData['time'],
            'destination' => $this->destination,
        ];
    }

}
