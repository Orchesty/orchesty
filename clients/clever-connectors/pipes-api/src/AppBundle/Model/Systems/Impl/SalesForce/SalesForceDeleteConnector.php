<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use Clue\React\Buzz\Browser;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
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

    private const   NODE_NAME = 'salesforce-delete-connector';
    protected const QUERY_URL = '%sservices/data/v40.0/query​​​All?q=%s';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * SalesForceDeleteConnector constructor.
     *
     * @param SalesForceSystem $system
     * @param DocumentManager  $dm
     */
    public function __construct(SalesForceSystem $system, DocumentManager $dm)
    {
        parent::__construct($system);
        $this->dm = $dm;
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

        $browser       = new Browser($loop);
        $systemInstall = $this->getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $baseUrl       = (string) $requestDto->getUri();

        $node = $this->dm->getRepository(Node::class)->findOneBy(['id' => $dto->getHeaders()['node_id']]);
        $top  = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $node->getTopology()]);
        /** @var LastSyncRepository $lastSync */
        $lastSync = $this->dm->getRepository(LastSync::class);

        $startTime = $lastSync->getLastSyncTime($systemInstall->getUser(), $top->getName(), self::NODE_NAME);
        $endTime   = new DateTime('now');
        $timeQuery = $this->getTimeQuery($startTime, $endTime) . '+AND+IsDeleted+TRUE';

        $countReq = $this->createCountRequest($baseUrl, $timeQuery);

        $promise = $this->fetchData($browser, $countReq)
            ->then(function (ResponseInterface $response): float {
                $data = json_decode($response->getBody()->getContents(), TRUE);

                return ceil($data['totalSize'] / self::PAGE_LIMIT);
            }
            )->then(function (float $total) use ($browser, $baseUrl, $callbackItem, $timeQuery) {
                $requests = [];
                for ($i = 0; $i < $total; $i++) {
                    $requests[] = $this
                        ->fetchData($browser, $this->createPageContactRequest($baseUrl, $timeQuery, $i))
                        ->then(function (ResponseInterface $response) use ($i): SuccessMessage {

                            return $this->createSuccessMessage($response, $i);
                        })->then($callbackItem);
                }

                return all($requests);
            }
            );

        if (!$lastSync) {
            $lastSync = new LastSync();
            $lastSync->setUser($systemInstall->getUser())
                ->setNodeName(self::NODE_NAME)
                ->setTopologyName($top->getName())
                ->setTimestamp($endTime);
            $this->dm->persist($lastSync);
        }
        $this->dm->flush($lastSync);

        return $promise;
    }

    /**
     * @param string $baseUrl
     * @param string $timeQuery
     * @param int    $page
     *
     * @return RequestInterface
     */
    private function createPageContactRequest(string $baseUrl, string $timeQuery, int $page): RequestInterface
    {
        $query = sprintf('select+email+from+contact%s+limit+%s,+%s', $timeQuery, self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page);

        return new Request('GET', sprintf(static::QUERY_URL, $baseUrl, $query));
    }

}