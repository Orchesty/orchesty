<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallFilter;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class GetApplicationForRefreshBatchConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class GetApplicationForRefreshBatchConnector extends BatchAbstract
{

    public const string NAME = 'get_application_for_refresh';

    /**
     * GetApplicationForRefreshBatchConnector constructor.
     *
     * @param ApplicationInstallRepository $repository
     */
    public function __construct(private readonly ApplicationInstallRepository $repository)
    {
        parent::__construct($this->repository);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     * @throws DateTimeException
     * @throws GuzzleException
     */
    public function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        $time = DateTimeUtils::getUtcDateTime('1 hour');

        $applications = $this->repository->findMany(new ApplicationInstallFilter(expires: $time->getTimestamp()));

        foreach ($applications as $app) {
            $dto->addItem([],$app->getUser());
        }

        return $dto;
    }

}
