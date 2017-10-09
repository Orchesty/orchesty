<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.10.17
 * Time: 17:36
 */

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
 * Class SalesForceUpdateConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
class SalesForceUpdateConnector extends SalesForceConnectorAbstract
{

    private const NODE_NAME = 'salesforce-connector';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * SalesForceUpdateConnector constructor.
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
        $headers = $dto->getHeaders();

        $startTime = $lastSync->getLastSyncTime($systemInstall->getUser(), $top->getName(), self::NODE_NAME);
        $endTime   = new DateTime('now');
        $timeQuery = $this->getTimeQuery($startTime, $endTime);

        $countReq = $this->createCountRequest($baseUrl, $headers, $timeQuery);

        $promise = $this->fetchData($browser, $countReq)
            ->then(function (ResponseInterface $response): int {
                return $this->getTotalPages($response);
            }
            )->then(function (int $total) use ($browser, $baseUrl, $callbackItem, $timeQuery, $headers) {
                $requests = [];
                for ($i = 0; $i < $total; $i++) {
                    $requests[] = $this
                        ->fetchData($browser, $this->createPageContactRequest($baseUrl, $headers, $timeQuery, $i))
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
        $this->dm->flush();

        return $promise;
    }

    /**
     * @param string $baseUrl
     * @param array  $headers
     * @param string $timeQuery
     * @param int    $page
     *
     * @return RequestInterface
     */
    private function createPageContactRequest(string $baseUrl, array $headers, string $timeQuery, int $page): RequestInterface
    {
        $query = sprintf('select+email,+firstname,+lastname+from+contact%s+limit+%s,+%s', $timeQuery, self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page);

        return new Request('GET', sprintf(self::QUERY_URL, $baseUrl, $query), $headers);
    }

}