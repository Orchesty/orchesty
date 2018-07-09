<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
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

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * AirtableContactConnectorAbstract constructor.
     *
     * @param AirtableSystem    $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     * @param DocumentManager   $dm
     */
    public function __construct(
        AirtableSystem $system,
        LastSyncManager $lastSyncManager,
        CurlSenderFactory $factory,
        DocumentManager $dm
    )
    {
        parent::__construct($system, $lastSyncManager, $factory, $dm);
        $this->dm = $dm;
    }

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

        $table = CMHeaders::get(AirtableSystem::TABLE_URL, $dto->getHeaders());
        $view  = CMHeaders::get(AirtableSystem::VIEW, $dto->getHeaders());

        $index = NULL;
        $from  = NULL;
        $sett  = $systemInstall->getSettings();
        $to    = new DateTime();

        foreach ($sett[SystemInstall::FORMS] as $index => $form) {
            if ($form[AirtableSystem::TABLE_URL] === $table) {
                if (array_key_exists(AirtableSystem::LAST_SYNC, $form)) {
                    $from = DateTime::createFromFormat(self::DATE_FORMAT, $form[AirtableSystem::LAST_SYNC]);
                } else {
                    $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
                    $from     = $lastSync->getTimestamp();
                }

                break;
            }
        }

        $promise = $this->getPage($sender, $requestDto, $table, $callbackItem, 1, NULL, $from, $view, $systemInstall);

        $sett[SystemInstall::FORMS][$index][AirtableSystem::LAST_SYNC] = $to->format(self::DATE_FORMAT);
        $systemInstall->setSettings($sett);
        $this->dm->flush();

        return $promise;
    }

    /**
     * @param CurlSender    $sender
     * @param RequestDto    $requestDto
     * @param string        $table
     * @param callable      $callbackItem
     * @param int           $page
     * @param null|string   $offset
     * @param DateTime|null $from
     * @param null|string   $view
     * @param SystemInstall $systemInstall
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        string $table,
        callable $callbackItem,
        int $page,
        ?string $offset = NULL,
        ?DateTime $from = NULL,
        ?string $view = NULL,
        SystemInstall $systemInstall
    ): PromiseInterface
    {
        $uri = $this->getUri($table, $offset, $from, $view);

        return $this->fetchData($sender, RequestDto::from($requestDto, $uri))->then(
            function (ResponseInterface $response) use (
                $sender,
                $requestDto,
                $table,
                $callbackItem,
                $page,
                $from,
                $view,
                $systemInstall
            ) {
                $data = json_decode($response->getBody()->getContents(), TRUE);

                if (!empty($data)) {
                    $callbackItem($this->createSuccessMessage($data, $page));
                }

                if ($this->hasOffset($data)) {
                    return $this->getPage(
                        $sender,
                        $requestDto,
                        $table,
                        $callbackItem,
                        $page + 1,
                        $this->getOffset($data),
                        $from,
                        $view,
                        $systemInstall
                    );
                } else {
                    return resolve();
                }
            },
            function (ResponseException $e) use ($systemInstall, $callbackItem, $page) {
                $success = $this->batchConnectorError($e, $this->system, $systemInstall, $page + 1);

                return $callbackItem($success);
            }
        );
    }

}