<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Notification\Model;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class NotificationManager
 *
 * @package Hanaboso\PipesFramework\Notification\Model
 */
class NotificationManager implements LoggerAwareInterface
{

    private const URL = '%s/notification_settings';

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var string
     */
    private $backend;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * NotificationManager constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param string               $backend
     */
    public function __construct(CurlManagerInterface $curlManager, string $backend)
    {
        $this->curlManager = $curlManager;
        $this->backend     = $backend;
        $this->logger      = new NullLogger();
    }

    /**
     * @return ResponseDto
     * @throws NotificationException
     * @throws CurlException
     */
    public function getSettings(): ResponseDto
    {
        $dto = new RequestDto(CurlManager::METHOD_GET, $this->getUrl(self::URL));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param array $data
     *
     * @return ResponseDto
     * @throws CurlException
     * @throws NotificationException
     * @throws JsonException
     */
    public function updateSettings(array $data): ResponseDto
    {
        $dto = (new RequestDto(CurlManager::METHOD_PUT, $this->getUrl(self::URL)))->setBody(Json::encode($data));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param string $url
     *
     * @return Uri
     */
    private function getUrl(string $url): Uri
    {
        return new Uri(sprintf($url, rtrim($this->backend, '/')));
    }

    /**
     * @param RequestDto $dto
     *
     * @return ResponseDto
     * @throws NotificationException
     */
    private function sendAndProcessRequest(RequestDto $dto): ResponseDto
    {
        $dto->setHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        try {
            return $this->curlManager->send($dto);
        } catch (Throwable $e) {
            $this->logger->error('Notification sender error.',
                ['Exception' => json_encode($e), 'Request' => json_encode($dto)]
            );

            throw new NotificationException(
                sprintf('Notification API failed: %s', $e->getMessage()),
                NotificationException::NOTIFICATION_EXCEPTION
            );
        }
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