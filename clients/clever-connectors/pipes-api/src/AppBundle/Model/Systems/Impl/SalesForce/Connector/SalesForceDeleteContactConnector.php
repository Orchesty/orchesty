<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce\Connector;

use CleverConnectors\AppBundle\Utils\CronUtils;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class SalesForceDeleteContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce\Connector
 */
class SalesForceDeleteContactConnector extends SalesForceContactConnectorAbstract
{

    protected const   NODE_NAME = 'salesforce-delete-contact-connector';
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
        $lastSync      = $this->lastSyncManager->getLastSync($data, $systemInstall, self::NODE_NAME);
        $times         = CronUtils::getTimes($lastSync);
        $timeQuery     = $this->getTimeQuery($times->getStart(), $times->getEnd()) . '+AND+IsDeleted=TRUE';
        $countReq      = $this->createCountRequest($requestDto, $timeQuery);

        $promise = $this->fetchData($browser, $countReq)
            ->then(function (ResponseInterface $response): int {
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
     * @return RequestInterface
     */
    protected function createPageContactRequest(
        int $page,
        string $timeQuery,
        RequestDto $dto
    ): RequestInterface
    {
        $query = sprintf(
            'select+email+from+contact%s+limit+%s+offset+%s', $timeQuery,
            self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page
        );

        return new Request('GET', sprintf(static::QUERY_URL, $dto->getUri(TRUE), $query), $dto->getHeaders());
    }

}