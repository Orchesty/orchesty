<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Notification\Model;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Nette\Utils\Json;
use Throwable;

/**
 * Class NotificationManager
 *
 * @package Hanaboso\PipesFramework\Notification\Model
 */
class NotificationManager
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
     * NotificationManager constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param string               $backend
     */
    public function __construct(CurlManagerInterface $curlManager, string $backend)
    {
        $this->curlManager = $curlManager;
        $this->backend     = $backend;
    }

    /**
     * @return ResponseDto
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
            throw new NotificationException(
                sprintf('Notification API failed: %s', $e->getMessage()),
                NotificationException::NOTIFICATION_EXCEPTION
            );
        }
    }

}