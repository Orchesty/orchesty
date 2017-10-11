<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 12:27
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CronUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class SalesForceSyncConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
class SalesForceSyncConnector extends SalesForceConnectorAbstract
{

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $systemInstallRepository;

    /**
     * SalesForceSyncConnector constructor.
     *
     * @param SalesForceSystem  $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     * @param DocumentManager   $dm
     */
    public function __construct(
        SalesForceSystem $system,
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

        $browser       = $this->factory->create($loop);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $baseUrl       = (string) $requestDto->getUri();
        $headers       = $requestDto->getHeaders();
        $countRequest  = $this->createCountRequest($baseUrl, $headers);

        $promise = $this->fetchData($browser, $countRequest)
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($browser, $baseUrl, $callbackItem, $headers) {
                    return all($this->doPageLoop($total, $browser, $baseUrl, $callbackItem, $headers));
                }
            );

        $this->systemInstallRepository->setSyncTime($systemInstall);

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
        string $timeQuery = ''
    ): RequestInterface
    {
        $query = sprintf(
            'select+email,+firstname,+lastname+from+contact+limit+%s+offset+%s',
            self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page
        );

        return new Request('GET', sprintf(self::QUERY_URL, $baseUrl, $query), $headers);
    }

}