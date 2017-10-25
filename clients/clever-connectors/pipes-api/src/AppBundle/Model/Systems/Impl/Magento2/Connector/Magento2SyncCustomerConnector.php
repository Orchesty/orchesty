<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Magento2System;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class Magento2SyncCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Connector
 */
class Magento2SyncCustomerConnector extends Magento2ConnectorAbstract
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * SalesForceSyncConnector constructor.
     *
     * @param Magento2System    $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     * @param DocumentManager   $dm
     */
    public function __construct(
        Magento2System $system,
        LastSyncManager $lastSyncManager,
        CurlSenderFactory $factory,
        DocumentManager $dm
    )
    {
        parent::__construct($system, $lastSyncManager, $factory);
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {

        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $promise = $this->fetchData($sender, $this->createCountRequest($requestDto))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($sender, $callbackItem, $requestDto) {
                    return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto));
                }
            );

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param int        $page
     * @param string     $timeQuery
     * @param RequestDto $dto
     *
     * @return RequestDto
     */
    protected function createPageContactRequest(int $page, string $timeQuery = '', RequestDto $dto): RequestDto
    {
        $query = sprintf(
            'searchCriteria[pageSize]=%s&searchCriteria[currentPage]=%s',
            self::PAGE_LIMIT,
            $page
        );
        $uri   = new Uri(sprintf(self::QUERY_URL, $dto->getUri(TRUE), $query));

        return RequestDto::from($dto, $uri);
    }

}