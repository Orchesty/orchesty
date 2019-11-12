<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class GetApplicationForRefreshBatch
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
class GetApplicationForRefreshBatch extends CustomNodeAbstract implements BatchInterface
{

    public const APPLICATION_ID = 'application-id';

    /**
     * @var ObjectRepository|ApplicationInstallRepository
     */
    private $repository;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {

        $this->repository = $dm->getRepository(ApplicationInstall::class);

    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $dto;
        $loop;
        $time = DateTimeUtils::getUtcDateTime('1 hour');
        /** @var ApplicationInstall[] $applications */
        $applications = $this->repository
            ->createQueryBuilder()
            ->select('_id')
            ->field('expires')->lte($time)
            ->field('expires')->notEqual(NULL)
            ->getQuery()
            ->execute();
        $i            = 1;
        foreach ($applications as $app) {
            $message = new SuccessMessage($i);
            $callbackItem($message->addHeader(PipesHeaders::createKey(self::APPLICATION_ID), $app->getId()));
            $i++;
        }

        return resolve();
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
