<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Traits\FacebookTrait;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;

/**
 * Class FacebookaudienceAdGenerator
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceAdGenerator implements CustomNodeInterface, BatchInterface
{

    use LoggerTrait;
    use FacebookTrait;

    private const URL = '/api-demo/fb/%s/ad/unprocessed';

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var string
     */
    private $aimUrl;

    /**
     * @var FacebookaudienceSystem
     */
    protected $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * FacebookAdGenerator constructor.
     *
     * @param FacebookaudienceSystem $system
     * @param DocumentManager        $dm
     * @param CurlManagerInterface   $curl
     * @param string                 $aimUrl
     */
    public function __construct(
        FacebookaudienceSystem $system,
        DocumentManager $dm,
        CurlManagerInterface $curl,
        string $aimUrl
    )
    {
        $this->curl                    = $curl;
        $this->aimUrl                  = rtrim($aimUrl, '/');
        $this->logger                  = new NullLogger();
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new CleverConnectorsException(
            'Process method not implemented.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $data = json_decode($dto->getData(), TRUE);
        if (!array_key_exists('user', $data)
            || !array_key_exists('token', $data)
            || !array_key_exists('client_id', $data)
        ) {
            throw new CleverConnectorsException(
                'Missing required field [user, token, client_id].'
            );
        }

        $msgs = [];
        $ads  = $this->fetchUnprocessedAds($data, $dto);
        foreach ($ads as $i => $ad) {
            if (is_null($ad['ref_id'] ?? NULL)) {
                continue;
            }

            $msgs[] = $this->createMessage($i, $data['client_id'], $dto->getHeaders(), $ad)->then($callbackItem);
        }

        return all($msgs);
    }

    /**
     * @param array      $data
     * @param ProcessDto $dto
     *
     * @return array
     * @throws CurlException
     */
    private function fetchUnprocessedAds(array $data, ProcessDto $dto): array
    {
        $req = new RequestDto(CurlManager::METHOD_GET, new Uri(sprintf($this->aimUrl . self::URL, $data['client_id'])));
        $req->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        $body = [];
        try {
            $res = $this->curl->send($req);
            $body = json_decode($res->getBody(), TRUE);
        } catch (CurlException $e) {
            $sys = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $this->logConnectorError($e, $sys, $this->system, $dto);
        }

        return $body;
    }

    /**
     * @param int    $i
     * @param string $client
     * @param array  $headers
     * @param array  $ad
     *
     * @return PromiseInterface
     */
    private function createMessage(
        int $i,
        string $client,
        array $headers,
        array $ad
    ): PromiseInterface
    {
        $message = new SuccessMessage($i);

        $message->addHeader(CMHeaders::createKey(CMHeaders::GUID), CMHeaders::get(CMHeaders::GUID, $headers));
        $message->addHeader(CMHeaders::createKey(CMHeaders::TOKEN), CMHeaders::get(CMHeaders::TOKEN, $headers));
        $message->addHeader(CMHeaders::createKey(CMHeaders::SYSTEM_KEY),
            CMHeaders::get(CMHeaders::SYSTEM_KEY, $headers));

        $message->setData(json_encode([
            'id'        => $ad['id'],
            'ref_id'    => $ad['ref_id'],
            'client_id' => $client,
        ]));

        return resolve($message);
    }

}