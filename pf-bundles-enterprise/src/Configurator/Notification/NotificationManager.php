<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Notification;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Throwable;

/**
 * Class NotificationManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Notification
 */
final class NotificationManager
{

    private const string SUBSCRIPTIONS_URL = '%s/api/subscriptions';
    private const string TENANT_ID         = 'orchesty';

    /**
     * NotificationManager constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param string               $notifierHost
     */
    public function __construct(private CurlManagerInterface $curlManager, private readonly string $notifierHost)
    {
    }

    /**
     * @param string $userId
     *
     * @return ResponseDto
     * @throws NotificationException
     */
    public function listSubscriptions(string $userId): ResponseDto
    {
        $dto = new RequestDto(
            $this->getUrl(self::SUBSCRIPTIONS_URL),
            CurlManager::METHOD_GET,
            new ProcessDto(),
        );

        return $this->sendRequest($dto, $userId);
    }

    /**
     * @param string $userId
     * @param string $body
     *
     * @return ResponseDto
     * @throws NotificationException
     */
    public function upsertSubscription(string $userId, string $body): ResponseDto
    {
        $dto = (new RequestDto(
            $this->getUrl(self::SUBSCRIPTIONS_URL),
            CurlManager::METHOD_PUT,
            new ProcessDto(),
        ))->setBody($body);

        return $this->sendRequest($dto, $userId);
    }

    /**
     * @param string $url
     *
     * @return Uri
     */
    private function getUrl(string $url): Uri
    {
        return new Uri(sprintf($url, rtrim($this->notifierHost, '/')));
    }

    /**
     * @param RequestDto $dto
     * @param string     $userId
     *
     * @return ResponseDto
     * @throws NotificationException
     */
    private function sendRequest(RequestDto $dto, string $userId): ResponseDto
    {
        $dto->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'X-Tenant-Id'  => self::TENANT_ID,
                'X-User-Id'    => $userId,
            ],
        );

        try {
            $response = $this->curlManager->send($dto, [RequestOptions::HTTP_ERRORS => FALSE]);
        } catch (Throwable $e) {
            throw new NotificationException(
                sprintf('Notifier API failed: %s', $e->getMessage()),
                NotificationException::NOTIFICATION_EXCEPTION,
            );
        }

        if ($response->getStatusCode() >= 400) {
            throw new NotificationException(
                sprintf('Notifier API error %d: %s', $response->getStatusCode(), $response->getBody()),
                NotificationException::NOTIFICATION_EXCEPTION,
            );
        }

        return $response;
    }

}
