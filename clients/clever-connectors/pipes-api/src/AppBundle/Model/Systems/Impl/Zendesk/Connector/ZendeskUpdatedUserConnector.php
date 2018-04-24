<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ZendeskUpdatedUserConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
class ZendeskUpdatedUserConnector extends ZendeskUserConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zendesk-updated-user-connector';
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     * @throws CleverConnectorsException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        $url = new Uri(sprintf('%s/api/v2/search?%s', rtrim($requestDto->getUri(TRUE), '/'),
            $this->getTimeQuery($times->getStart())));

        $promise = $this->getPage($sender, $callbackItem, RequestDto::from($requestDto, $url), 1, NULL, $systemInstall);

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @param mixed $data
     * @param int   $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage($data, int $page): SuccessMessage
    {
        if (array_key_exists('results', $data)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($data['results']));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Bad response data for Zendesk sync request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

    /**
     * @param DateTime|null $start
     *
     * @return string
     */
    private function getTimeQuery(?DateTime $start): string
    {
        if (!$start) {
            return 'query=type:user';
        }

        $time = rtrim($start->format(DateTime::ISO8601), '+0000') . 'Z';

        return 'query=type:user updated>' . $time;
    }

}