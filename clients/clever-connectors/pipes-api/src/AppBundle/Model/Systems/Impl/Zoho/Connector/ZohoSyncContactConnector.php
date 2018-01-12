<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Arrays;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ZohoSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
class ZohoSyncContactConnector extends ZohoContactConnectorAbstract
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * ZohoContactConnectorAbstract constructor.
     *
     * @param ZohoSystem             $system
     * @param CurlSenderFactory      $factory
     * @param DocumentManager        $dm
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        ZohoSystem $system,
        CurlSenderFactory $factory,
        DocumentManager $dm,
        ProgressCounterService $counterService
    )
    {
        parent::__construct($system, $factory, $counterService);
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zoho-sync-contact-connector';
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
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders());

        $promise = $this->getPage($sender, $requestDto, $callbackItem, 1, $systemInstall, NULL, $processId);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws SystemException
     */
    protected function isEmpty(array $data): bool
    {
        parent::isEmpty($data);

        return array_key_exists('nodata', $data['response']);
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
        if (array_key_exists('result', $data['response'])
            && array_key_exists('Contacts', $data['response']['result'])
            && array_key_exists('row', $data['response']['result']['Contacts'])
        ) {
            $successMessage = new SuccessMessage($i);
            $data           = $data['response']['result']['Contacts']['row'];
            if (!Arrays::isList($data)) {
                $data = [$data];
            }

            $successMessage->setData(json_encode($data));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Bad response data for ZOHO sync request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

    /**
     * @param RequestDto    $dto
     * @param int           $page
     * @param DateTime|null $from
     *
     * @return Uri
     */
    protected function getUri(RequestDto $dto, int $page, ?DateTime $from = NULL): Uri
    {
        $i = $page * self::ITEMS_PER_PAGE;

        return new Uri(sprintf(urldecode($dto->getUri(TRUE)) . '&fromIndex=%s&toIndex=%s',
            'getRecords',
            $i - self::ITEMS_PER_PAGE,
            $i - 1));
    }

}