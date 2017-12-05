<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class AirtableUpdatedContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
class AirtableUpdatedContactConnector extends AirtableContactConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'airtable-updated-contact-connector';
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        $promise = $this->getPage($sender, $requestDto, $callbackItem, 1, NULL, $times->getStart());

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @param CurlSender    $sender
     * @param RequestDto    $requestDto
     * @param callable      $callbackItem
     * @param int           $page
     * @param string  |null $offset
     * @param DateTime|null $from
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        int $page,
        ?string $offset = NULL,
        ?DateTime $from = NULL
    ): PromiseInterface
    {
        $uri = $this->getUri($requestDto, $offset, $from);

        return $this->fetchData($sender, RequestDto::from($requestDto, $uri))->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $callbackItem, $page, $from) {
                $data = json_decode($response->getBody()->getContents(), TRUE);
                $callbackItem($this->createSuccessMessage($data, $page));

                if ($this->hasOffset($data)) {
                    return $this->getPage(
                        $sender,
                        $requestDto,
                        $callbackItem,
                        $page + 1,
                        $this->getOffset($data),
                        $from
                    );
                } else {
                    return resolve();
                }
            }
        );
    }

}