<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class ShipstationUpdateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Connector
 */
class ShipstationUpdateCustomerConnector extends ShipstationCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shipstation-update-customer-connector';
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $browser       = $this->factory->create($loop);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        $promise = $this->fetchData($browser, $this->createCountRequest($requestDto))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                },
                function (ResponseException $e) use ($systemInstall, $callbackItem) {
                    $success = $this->batchConnectorError($e, $this->system, $systemInstall, 1);

                    return $callbackItem($success);
                }
            )->then(
                function (int $total) use ($browser, $callbackItem, $requestDto, $systemInstall) {
                    return all($this->doPageLoop($total, $browser, $callbackItem, $requestDto, $systemInstall));
                }
            );

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @param int        $page
     * @param RequestDto $dto
     *
     * @return RequestDto
     */
    protected function createPageContactRequest(int $page, RequestDto $dto): RequestDto
    {
        $query = sprintf('page=%s&pageSize=%s', $page, self::PAGE_LIMIT);
        $uri   = new Uri(sprintf(self::QUERY_URL, $dto->getUri(TRUE), $query));

        return RequestDto::from($dto, $uri);
    }

}