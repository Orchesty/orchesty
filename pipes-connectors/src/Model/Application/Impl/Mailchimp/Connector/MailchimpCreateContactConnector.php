<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class MailchimpCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector
 */
class MailchimpCreateContactConnector extends ConnectorAbstract
{

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var ObjectRepository|ApplicationInstallRepository
     */
    private $repository;

    /**
     * MailchimpCreateContactConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(
        CurlManagerInterface $curlManager,
        DocumentManager $dm
    )
    {
        $this->curlManager = $curlManager;
        $this->repository  = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'mailchimp_create_contact';
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
            'ProcessEvent is not implemented',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);

        $apiEndpoint = $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT];

        $return = $this->curlManager->send(
            $this->application->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                sprintf(
                    '%s/3.0/lists/%s/members/',
                    $apiEndpoint,
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][MailchimpApplication::AUDIENCE_ID]
                ),
                $dto->getData()
            )
        );

        $json = $return->getJsonBody();

        unset($json['type'], $json['detail'], $json['instance']);

        $dto->setData((string) json_encode($json, JSON_THROW_ON_ERROR, 512));

        $statusCode = $return->getStatusCode();

        $this->evaluateStatusCode($statusCode, $dto);

        return $dto;
    }

}

