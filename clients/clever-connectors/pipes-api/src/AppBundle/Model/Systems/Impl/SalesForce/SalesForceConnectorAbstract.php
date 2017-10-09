<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Clue\React\Buzz\Browser;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Class SalesForceConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
abstract class SalesForceConnectorAbstract implements BatchInterface, ConnectorInterface
{

    protected const QUERY_URL  = '%s/services/data/v40.0/query?q=%s';
    protected const PAGE_LIMIT = 50;
    protected const NODE_NAME  = '';

    /**
     * @var SalesForceSystem
     */
    protected $system;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $repo;

    /**
     * SalesForceDeleteConnector constructor.
     *
     * @param SalesForceSystem $system
     * @param DocumentManager  $dm
     */
    public function __construct(SalesForceSystem $system, DocumentManager $dm)
    {
        $this->system = $system;
        $this->dm     = $dm;
        $this->repo   = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('SalesForce has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('SalesForce has not implemented "processAction" function.');
    }

    /**
     * @param DateTime|null $from
     * @param DateTime      $to
     *
     * @return string
     */
    protected function getTimeQuery(?DateTime $from, DateTime $to): string
    {
        $timeQuery = '+';

        if ($from) {
            $timeQuery .= ltrim(http_build_query(['q' => 'where LastModifiedDate>' . $from->format(DateTime::ISO8601)]),
                'q=');
        }
        $timeQuery .= ($timeQuery === '+' ? '' : 'and+') .
            ltrim(http_build_query(['q' => 'where LastModifiedDate<=' . $to->format(DateTime::ISO8601)]), 'q=');

        return $timeQuery;
    }

    /**
     * @param string $baseUrl
     * @param array  $headers
     * @param string $timeQuery
     *
     * @return RequestInterface
     */
    protected function createCountRequest(string $baseUrl, array $headers, string $timeQuery = ''): RequestInterface
    {
        $query = 'select+count()+from+contact' . $timeQuery;

        return new Request('GET', sprintf(static::QUERY_URL, $baseUrl, $query), $headers);
    }

    /**
     * @param Browser          $browser
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    protected function fetchData(Browser $browser, RequestInterface $request): PromiseInterface
    {
        return $browser->send($request);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    protected function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        $system = SystemInstall::from(json_decode($dto->getData(), TRUE)['data']);

        return $this->repo->getSystemInstall($system->getUser(), $system->getToken(), $system->getSystem());
    }

    /**
     * @param ResponseInterface $response
     * @param int               $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage(ResponseInterface $response, int $page): SuccessMessage
    {
        $res = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($res) && array_key_exists('records', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['records']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [records] key in response data from SalesForce.',
            SystemException::MISSING_DATA
        );
    }

    /**
     * @param ResponseInterface $response
     *
     * @return int
     * @throws SystemException
     */
    protected function getTotalPages(ResponseInterface $response): int
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('totalSize', $data)) {
            throw new SystemException(
                'SalesForce response has no "totalSize" field!',
                SystemException::MISSING_DATA
            );
        }

        $total = (int) ceil($data['totalSize'] / self::PAGE_LIMIT);
        unset($data);

        return $total;
    }

    /**
     * @param ProcessDto    $dto
     * @param SystemInstall $systemInstall
     * @param string        $topologyName
     *
     * @return LastSync
     * @throws SystemException
     */
    protected function getLastSync(ProcessDto $dto, SystemInstall $systemInstall, string &$topologyName): LastSync
    {
        if (!array_key_exists('node_id', $dto->getHeaders())) {
            throw new SystemException(
                'Missing [node_id] in ProcessDto.',
                SystemException::MISSING_DATA
            );
        }

        $node         = $this->dm->getRepository(Node::class)->findOneBy(['id' => GeneratorUtils::denormalizeName($dto->getHeaders()['node_id'])]);
        $top          = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $node->getTopology()]);
        $topologyName = $top->getName();
        /** @var LastSyncRepository $repo */
        $repo = $this->dm->getRepository(LastSync::class);

        $lastSync = $repo->getLastSyncTime($systemInstall->getUser(), $topologyName, static::NODE_NAME);

        if (!$lastSync) {
            $lastSync = $this->createLastSync($systemInstall, self::NODE_NAME, $topologyName);
        }

        if ($systemInstall->isSynchronized() && $systemInstall->getSynchronizedTime()) {
            $lastSync->setTimestamp($systemInstall->getSynchronizedTime());
        }

        return $lastSync;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $node
     * @param string        $topology
     *
     * @return LastSync
     */
    protected function createLastSync(SystemInstall $systemInstall, string $node, string $topology): LastSync
    {
        $lastSync = new LastSync();
        $lastSync
            ->setUser($systemInstall->getUser())
            ->setNodeName($node)
            ->setTopologyName($topology);
        $this->dm->persist($lastSync);

        return $lastSync;
    }

    /**
     * @param int      $total
     * @param Browser  $browser
     * @param string   $baseUrl
     * @param callable $callbackItem
     * @param array    $headers
     * @param string   $timeQuery
     *
     * @return array
     */
    protected function doPageLoop(
        int $total,
        Browser $browser,
        string $baseUrl,
        callable $callbackItem,
        array $headers,
        string $timeQuery = ''
    ): array
    {
        $requests = [];
        for ($i = 0; $i < $total; $i++) {
            $requests[] = $this
                ->fetchData($browser, $this->createPageContactRequest($baseUrl, $i, $headers, $timeQuery))
                ->then(function (ResponseInterface $response) use ($i): SuccessMessage {

                    return $this->createSuccessMessage($response, $i);
                })->then($callbackItem);
        }

        return $requests;
    }

    /**
     * @param string $baseUrl
     * @param int    $page
     * @param array  $headers
     * @param string $timeQuery
     *
     * @return RequestInterface
     * @throws Exception
     */
    abstract protected function createPageContactRequest(
        string $baseUrl,
        int $page,
        array $headers,
        string $timeQuery
    ): RequestInterface;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'salesforce';
    }

}