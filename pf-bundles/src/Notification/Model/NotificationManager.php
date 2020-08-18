<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Notification\Model;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\Utils\String\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class NotificationManager
 *
 * @package Hanaboso\PipesFramework\Notification\Model
 */
final class NotificationManager implements LoggerAwareInterface
{

    private const LIST = '%s/notifications/settings';
    private const GET  = '%s/notifications/settings/%s';
    private const SAVE = '%s/notifications/settings/%s';

    /**
     * @var CurlManagerInterface
     */
    private CurlManagerInterface $curlManager;

    /**
     * @var string
     */
    private string $backend;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

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
        $dto = new RequestDto(CurlManager::METHOD_GET, $this->getUrl(self::LIST));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param string $id
     *
     * @return ResponseDto
     * @throws CurlException
     * @throws NotificationException
     */
    public function getSetting(string $id): ResponseDto
    {
        $dto = new RequestDto(CurlManager::METHOD_GET, $this->getUrl(self::GET, $id));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return ResponseDto
     * @throws CurlException
     * @throws NotificationException
     */
    public function updateSettings(string $id, array $data): ResponseDto
    {
        $dto = (new RequestDto(CurlManager::METHOD_PUT, $this->getUrl(self::SAVE, $id)))
            ->setBody(Json::encode($data));

        return $this->sendAndProcessRequest($dto);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $url
     * @param string ...$parameters
     *
     * @return Uri
     */
    private function getUrl(string $url, ?string ...$parameters): Uri
    {
        return new Uri(sprintf($url, rtrim($this->backend, '/'), ...$parameters));
    }

    /**
     * @param RequestDto $dto
     *
     * @return ResponseDto
     * @throws NotificationException
     */
    private function sendAndProcessRequest(RequestDto $dto): ResponseDto
    {
        $dto->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ]
        );

        try {
            return $this->curlManager->send($dto);
        } catch (Throwable $e) {
            $this->logger->error(
                'Notification sender error.',
                [
                    'Exception' => Json::encode($e),
                    'Request'   => Json::encode($dto),
                ]
            );

            throw new NotificationException(
                sprintf('Notification API failed: %s', $e->getMessage()),
                NotificationException::NOTIFICATION_EXCEPTION
            );
        }
    }

}
