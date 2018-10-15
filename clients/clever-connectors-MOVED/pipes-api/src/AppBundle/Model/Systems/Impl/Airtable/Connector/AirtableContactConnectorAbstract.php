<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;

/**
 * Class AirtableContactConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
abstract class AirtableContactConnectorAbstract implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    protected const PAGE_LIMIT = 50;

    /**
     * @var AirtableSystem
     */
    protected $system;

    /**
     * @var LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

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
        $this->system                  = $system;
        $this->lastSyncManager         = $lastSyncManager;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Airtable has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Airtable has not implemented "processAction" function.');
    }

    /**
     * @param string        $table
     * @param null|string   $offset
     * @param DateTime|null $from
     * @param null|string   $view
     *
     * @return Uri
     */
    protected function getUri(
        string $table,
        ?string $offset = NULL,
        ?DateTime $from = NULL,
        ?string $view = NULL
    ): Uri
    {
        $uri = $table;

        $uri .= sprintf('?pageSize=%s', self::PAGE_LIMIT);

        if ($view) {
            $uri .= sprintf('&view=%s', $view);
        }
        if ($offset) {
            $uri .= sprintf('&offset=%s', $offset);
        }
        if ($from) {
            $uri .= sprintf('&filterByFormula=CREATED_TIME()>\'%s\'', urlencode($from->format(DATE_ISO8601)));
        }

        return new Uri($uri);
    }

    /**
     * @param array $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage(array $data, int $i): SuccessMessage
    {
        if (array_key_exists('records', $data)) {
            if (!empty($data['records'])) {
                $successMessage = new SuccessMessage($i);
                $successMessage->setData(json_encode($this->removeEmptyRecords($data['records'])));
                unset($data);

                return $successMessage;
            } else {
                throw new SystemException(
                    'Empty data set, Airtable connector.',
                    SystemException::MISSING_DATA
                );
            }
        } else {
            throw new SystemException(
                'Bad response data for Airtable sync request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function hasOffset(array $data): bool
    {
        if (array_key_exists('offset', $data)) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getOffset(array $data): string
    {
        return $data['offset'];
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $request
     *
     * @return PromiseInterface
     */
    protected function fetchData(CurlSender $sender, RequestDto $request): PromiseInterface
    {
        return $sender->send($request);
    }

    /**
     * @param array $records
     *
     * @return array
     */
    private function removeEmptyRecords(array $records): array
    {
        $res = [];
        foreach ($records as $record) {
            if (array_key_exists('fields', $record) && is_array($record['fields']) && !empty($record['fields'])) {
                $res[] = $record;
            }
        }

        return $res;
    }

}