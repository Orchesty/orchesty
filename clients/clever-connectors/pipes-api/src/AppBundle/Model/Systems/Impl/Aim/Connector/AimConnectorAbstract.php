<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
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
    private $type;
    /**
     * @var string
     */
    private $url;

    /**
     * @param AimSystem            $system
     * @param CurlManagerInterface $curl
     * @param string               $type
     * @param string               $url
     */
    public function __construct(
        AimSystem $system,
        CurlManagerInterface $curl,
        string $type,
        string $url
    )
    {
        $this->system = $system;
        $this->curl   = $curl;
        $this->type   = $type;
        $this->url    = $url;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'aim-' . $this->type;
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
        $action = $dto->getHeader(CMHeaders::createKey(AimSystem::HEADER_ACTION), NULL);
        switch ($action) {
            case AimSystem::SYNC_ACTION:
                return $this->actionUpsert($dto);

            case AimSystem::DELETE_ACTION:
                return $this->actionDelete($dto);

            default:
                throw new ConnectorException(
                    sprintf('Invalid action "%s"', $action),
                    ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
                );
        }
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    private function actionUpsert(ProcessDto $dto): ProcessDto
    {
        return $this->runAction($dto, CurlManager::METHOD_POST);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    private function actionDelete(ProcessDto $dto): ProcessDto
    {
        return $this->runAction($dto, CurlManager::METHOD_DELETE);
    }

    /**
     * @param ProcessDto $dto
     * @param string     $method
     *
     * @return ProcessDto
     * @throws CurlException
     */
    private function runAction(ProcessDto $dto, string $method): ProcessDto
    {
        $request = new RequestDto($method, new Uri($this->url));
        $request->setBody($dto->getData());
        $request->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        try {
            $response     = $this->curl->send($request);
            $responseBody = json_decode($response->getBody(), TRUE);
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, new SystemInstall(), $dto);
        }

        return $dto->setData(json_encode($responseBody));
    }

}
