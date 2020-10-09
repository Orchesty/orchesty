<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class GetApplicationForRefreshBatchConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class GetApplicationForRefreshBatchConnector extends ConnectorAbstract implements BatchInterface
{

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;
    use BatchTrait;

    public const APPLICATION_ID = 'get_application_for_refresh';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private $repository;

    /**
     * GetApplicationForRefreshBatchConnector constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::APPLICATION_ID;
    }

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $dto;
        $i    = 1;
        $time = DateTimeUtils::getUtcDateTime('1 hour');

        /** @var ApplicationInstall[] $applications */
        $applications = $this->repository
            ->createQueryBuilder()
            ->select('_id')
            ->field('expires')->lte($time)
            ->field('expires')->notEqual(NULL)
            ->getQuery()
            ->execute();

        foreach ($applications as $app) {
            $message = new SuccessMessage($i);
            $callbackItem($message->addHeader(PipesHeaders::createKey(self::APPLICATION_ID), $app->getId()));
            $i++;
        }

        return $this->createPromise();
    }

}
