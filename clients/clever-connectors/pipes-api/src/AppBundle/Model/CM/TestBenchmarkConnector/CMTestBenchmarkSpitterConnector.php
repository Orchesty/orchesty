<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\TestBenchmarkConnector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Created by PhpStorm.
 * User: lukas.hlavac & radek.jirsa
 * Date: 1/16/18
 * Time: 5:50 PM
 */
class CMTestBenchmarkSpitterConnector implements ConnectorInterface, LoggerAwareInterface
{

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;
    /**
     * @var string
     */
    private $spitterHost;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private const BLACKHOLE_URI = '/blackhole';

    /**
     * CMTestBenchmarkSpitterConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param string               $spitterHost
     */
    public function __construct(CurlManagerInterface $curlManager, string $spitterHost)
    {
        $this->curlManager = $curlManager;
        $this->spitterHost = $spitterHost;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'cleverconnectors-benchmark-spitter-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Not implemented', ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $request = new RequestDto(CurlManager::METHOD_POST, new Uri($this->spitterHost . self::BLACKHOLE_URI));
        $request->setBody($dto->getData());

        try {
            $response = $this->curlManager->send($request);
            $dto->setData($response->getBody());
        } catch (Throwable $e) {
            var_dump($e->getMessage());
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return $dto;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}