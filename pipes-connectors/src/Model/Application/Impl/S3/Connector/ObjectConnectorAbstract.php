<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\S3\Connector;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Throwable;

/**
 * Class ObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\S3\Connector
 */
abstract class ObjectConnectorAbstract extends ConnectorAbstract
{

    protected const BUCKET = 'Bucket';
    protected const KEY    = 'Key';
    protected const SOURCE = 'SourceFile';
    protected const TARGET = 'SaveAs';

    protected const NAME    = 'name';
    protected const CONTENT = 'content';

    /**
     * @var ApplicationInstallRepository|ObjectRepository
     */
    protected $repository;

    /**
     * ObjectConnectorAbstract constructor.
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
        return sprintf('s3-%s', $this->getCustomId());
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
     * @return S3Application
     */
    protected function getApplication(): S3Application
    {
        /** @var S3Application $application */
        $application = $this->application;

        return $application;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    protected function getBucket(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[BasicApplicationAbstract::FORM][S3Application::BUCKET];
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     */
    protected function getContent(ProcessDto $dto): array
    {
        return json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param ProcessDto $dto
     * @param array      $content
     *
     * @return ProcessDto
     */
    protected function setContent(ProcessDto $dto, array $content): ProcessDto
    {
        return $dto->setData(json_encode($content, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array $parameters
     * @param array $content
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
    ): OnRepeatException {
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
