<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;

/**
 * Class AwsObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps
 */
abstract class AwsObjectConnectorAbstract extends ConnectorAbstract
{

    use ProcessExceptionTrait;
    use ProcessEventNotSupportedTrait;

    protected const QUERY  = 'query';
    protected const RESULT = 'result';

    protected const BUCKET = 'Bucket';
    protected const KEY    = 'Key';
    protected const SOURCE = 'SourceFile';
    protected const TARGET = 'SaveAs';

    protected const NAME    = 'name';
    protected const CONTENT = 'content';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    protected $repository;

    /**
     * @return string
     */
    abstract protected function getCustomId(): string;

    /**
     * AwsObjectConnectorAbstract constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    protected function getApplicationInstall(ProcessDto $dto): ApplicationInstall
    {
        return $this->repository->findUserAppByHeaders($dto);
    }

    /**
     * @param mixed[] $parameters
     * @param mixed[] $content
     *
     * @throws ConnectorException
     */
    protected function checkParameters(array $parameters, array $content): void
    {
        foreach ($parameters as $parameter) {
            if (!isset($content[$parameter])) {
                throw $this->createException("Required parameter '%s' is not provided!", $parameter);
            }
        }
    }

}
