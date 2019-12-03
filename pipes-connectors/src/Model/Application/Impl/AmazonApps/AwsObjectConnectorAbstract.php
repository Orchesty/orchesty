<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Throwable;

/**
 * Class AwsObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps
 */
abstract class AwsObjectConnectorAbstract extends ConnectorAbstract
{

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
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(
            sprintf("Method '%s' is not implemented!", __METHOD__),
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    protected function getApplicationInstall(ProcessDto $dto): ApplicationInstall
    {
        return $this->repository->findUsersAppDefaultHeaders($dto);
    }

    /**
     * @return RedshiftApplication|S3Application
     */
    protected function getApplication()
    {
        /** @var RedshiftApplication|S3Application $application */
        $application = $this->application;

        return $application;
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

    /**
     * @param string $message
     * @param string ...$arguments
     *
     * @return ConnectorException
     */
    protected function createException(string $message, string ...$arguments): ConnectorException
    {
        $message = sprintf("Connector '%s': %s", $this->getId(), $message);

        if ($arguments) {
            $message = sprintf($message, ...$arguments);
        }

        return new ConnectorException($message, ConnectorException::CONNECTOR_FAILED_TO_PROCESS);
    }

    /**
     * @param ProcessDto $dto
     * @param Throwable  $throwable
     * @param string     ...$arguments
     *
     * @return OnRepeatException
     */
    protected function createRepeatException(
        ProcessDto $dto,
        Throwable $throwable,
        string ...$arguments
    ): OnRepeatException
    {
        $message = sprintf("Connector '%s': %s: %s", $this->getId(), get_class($throwable), $throwable->getMessage());

        if ($arguments) {
            $message = sprintf($message, ...$arguments);
        }

        return (new OnRepeatException($dto, $message, $throwable->getCode(), $throwable))
            ->setInterval(60000)
            ->setMaxHops(2);
    }

    /**
     * @return string
     */
    protected abstract function getCustomId(): string;

}