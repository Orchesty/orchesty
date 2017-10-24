<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ZohoCronConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
abstract class ZohoCronConnectorAbstract extends ZohoContactConnectorAbstract
{

    /**
     * @var LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * ZohoContactConnectorAbstract constructor.
     *
     * @param ZohoSystem        $system
     * @param CurlSenderFactory $factory
     * @param LastSyncManager   $lastSyncManager
     */
    public function __construct(
        ZohoSystem $system,
        CurlSenderFactory $factory,
        LastSyncManager $lastSyncManager
    )
    {
        parent::__construct($system, $factory);
        $this->lastSyncManager = $lastSyncManager;
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
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        $promise = $this->getPage($sender, $requestDto, $callbackItem, 1, $times->getStart());

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @param DateTime|null $time
     *
     * @return string
     */
    protected function formatTime(?DateTime $time): string
    {
        if (!$time) {
            return '';
        }

        return sprintf('&lastModifiedTime=%s', $time->format('Y-m-d+H:i:s'));
    }

}