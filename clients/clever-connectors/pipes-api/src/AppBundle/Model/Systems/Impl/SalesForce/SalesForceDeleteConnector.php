<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Utils\CronUtils;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class SalesForceDeleteConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
class SalesForceDeleteConnector extends SalesForceConnectorAbstract
{

    protected const   NODE_NAME = 'salesforce-delete-connector';
    protected const   QUERY_URL = '%s/services/data/v40.0/queryAll?q=%s';

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
        $data          = CronUtils::parseData($dto);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $baseUrl       = (string) $requestDto->getUri();
        $headers       = $requestDto->getHeaders();

        $lastSync = $this->lastSyncManager->getLastSync($data, $systemInstall, self::NODE_NAME);
        $times    = CronUtils::getTimes($lastSync);

        $timeQuery = $this->getTimeQuery($times->getStart(), $times->getEnd()) . '+AND+IsDeleted=TRUE';
        $countReq  = $this->createCountRequest($baseUrl, $headers, $timeQuery);

        $promise = $this->fetchData($browser, $countReq)
            ->then(function (ResponseInterface $response): int {
                return $this->getTotalPages($response);
            }
            )->then(
                function (int $total) use ($browser, $baseUrl, $callbackItem, $timeQuery, $headers) {
                    return all($this->doPageLoop($total, $browser, $baseUrl, $callbackItem, $headers, $timeQuery));
                }
            );

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @param string $baseUrl
     * @param int    $page
     * @param array  $headers
     * @param string $timeQuery
     *
     * @return RequestInterface
     */
    protected function createPageContactRequest(
        string $baseUrl,
        int $page,
        array $headers,
        string $timeQuery
    ): RequestInterface
    {
        $query = sprintf(
            'select+email+from+contact%s+limit+%s+offset+%s', $timeQuery,
            self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page
        );

        return new Request('GET', sprintf(static::QUERY_URL, $baseUrl, $query), $headers);
    }

}