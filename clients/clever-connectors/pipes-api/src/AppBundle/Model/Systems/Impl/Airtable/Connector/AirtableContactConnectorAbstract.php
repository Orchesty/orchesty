<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use React\Promise\PromiseInterface;

/**
 * Class AirtableContactConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
abstract class AirtableContactConnectorAbstract implements BatchInterface, ConnectorInterface
{

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
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Airtable has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Airtable has not implemented "processAction" function.');
    }

    /**
     * @param RequestDto    $dto
     * @param null|string   $offset
     * @param DateTime|null $from
     *
     * @return Uri
     */
    protected function getUri(RequestDto $dto, ?string $offset = NULL, ?DateTime $from = NULL): Uri
    {
        $query = NULL;
        $uri   = $dto->getUri(TRUE);

        if (strpos($uri, '?')) {
            $tmp   = explode('?', $uri);
            $uri   = $tmp[0];
            $query = $tmp[1];
        }

        $uri .= sprintf('?pageSize=%s', self::PAGE_LIMIT);
        if ($offset) {
            $uri .= sprintf('&offset=%s', $offset);
        }
        if ($from) {
            $uri .= sprintf('&filterByFormula=CREATED_TIME()>\'%s\'', urlencode($from->format(DATE_ISO8601)));
        }
        if ($query) {
            $uri .= sprintf('&%s', $query);
        }

        return new Uri($uri);
    }

    /**
     * @param mixed $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage($data, int $i): SuccessMessage
    {
        if (array_key_exists('records', $data)) {

            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['records']));
            unset($data);

            return $successMessage;
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

}