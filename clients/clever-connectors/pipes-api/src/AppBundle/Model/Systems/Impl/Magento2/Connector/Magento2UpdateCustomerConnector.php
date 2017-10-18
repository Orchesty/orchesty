<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Connector;

use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class Magento2UpdateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Connector
 */
class Magento2UpdateCustomerConnector extends Magento2ConnectorAbstract
{

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $browser       = $this->factory->create($loop);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $lastSync  = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times     = CronUtils::getTimes($lastSync);
        $timeQuery = $this->getTimeQuery($times->getStart(), $times->getEnd());

        $promise = $this->fetchData($browser, $this->createCountRequest($requestDto, $timeQuery))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($browser, $callbackItem, $timeQuery, $requestDto) {
                    return all($this->doPageLoop($total, $browser, $callbackItem, $requestDto, $timeQuery));
                }
            );

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @param int        $page
     * @param string     $timeQuery
     * @param RequestDto $dto
     *
     * @return RequestDto
     */
    protected function createPageContactRequest(int $page, string $timeQuery, RequestDto $dto): RequestDto
    {
        $query = sprintf(
            'searchCriteria[pageSize]=%s&searchCriteria[currentPage]=%s&' . $timeQuery,
            self::PAGE_LIMIT,
            $page
        );

        $uri = new Uri(sprintf(self::QUERY_URL, $dto->getUri(TRUE), $query));

        return RequestDto::from($dto, $uri);
    }

}