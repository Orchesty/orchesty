<?php
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/23/17
 * Time: 11:19 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use React\Promise\PromiseInterface;

class QuickbooksSyncCustomerConnector extends QuickbooksCustomerConnectorAbstract
{

    /**
     * @var DocumentRepository
     */
    private $systemInstallRepository;

    /**
     * QuickbooksSyncCustomerConnector constructor.
     *
     * @param QuickbooksSystem  $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     * @param DocumentManager   $dm
     */
    public function __construct(
        QuickbooksSystem $system,
        LastSyncManager $lastSyncManager,
        CurlSenderFactory $factory,
        DocumentManager $dm
    )
    {
        parent::__construct($system, $lastSyncManager, $factory);
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-sync-customer-connector';
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return string
     */
    protected function getTotalQuery(SystemInstall $systemInstall, ProcessDto $dto): string
    {
        return 'SELECT COUNT(*) FROM customer WHERE Active = true';
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     * @param int           $start
     * @param int           $count
     *
     * @return string
     */
    protected function getDataQuery(SystemInstall $systemInstall, ProcessDto $dto, int $start, int $count): string
    {
        return sprintf('SELECT * FROM customer WHERE Active = true STARTPOSITION %d MAXRESULTS %d', $start, $count);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    protected function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        return $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
    }

    /**
     * @param CurlSender    $sender
     * @param RequestDto    $dto
     *
     * @param SystemInstall $systemInstall
     *
     * @return PromiseInterface
     */
    protected function fetchData(CurlSender $sender, RequestDto $dto, SystemInstall $systemInstall): PromiseInterface
    {
        $promise = parent::fetchData($sender, $dto, $systemInstall);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

}