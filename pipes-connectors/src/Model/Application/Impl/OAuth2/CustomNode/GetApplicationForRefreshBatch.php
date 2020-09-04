<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class GetApplicationForRefreshBatch
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode
 */
final class GetApplicationForRefreshBatch extends CustomNodeAbstract implements BatchInterface
{

    use BatchTrait;

    public const APPLICATION_ID = 'application-id';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private $repository;

    /**
     * GetApplicationForRefreshBatch constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
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

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(
            'Process is not implemented',
            ConnectorException::CUSTOM_NODE_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

}
