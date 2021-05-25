<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class FlexiBeeGetContactsArrayConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeGetContactsArrayConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    private const ID = 'flexibee.get-contacts-array';

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * @var FlexiBeeApplication
     */
    private FlexiBeeApplication $app;

    /**
     * FlexiBeeGetContactsArrayConnector constructor.
     *
     * @param DocumentManager $dm
     * @param CurlManager     $sender
     */
    public function __construct(DocumentManager $dm, private CurlManager $sender)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::ID;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        try {

            $applicationInstall = $this->repository->findUserAppByHeaders($dto);

            /** @var FlexiBeeApplication $application */
            $application = $this->getApplication();
            $request     = $application
                ->getRequestDto(
                    $applicationInstall,
                    CurlManager::METHOD_GET,
                    (string) $application->getUrl($applicationInstall, 'kontakt.json'),
                )->setDebugInfo($dto);

            $response = $this->sender->send($request);

            $this->evaluateStatusCode($response->getStatusCode(), $dto);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        }

        return $dto;
    }

}
