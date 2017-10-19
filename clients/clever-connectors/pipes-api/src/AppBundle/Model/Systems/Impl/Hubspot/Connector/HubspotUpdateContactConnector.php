<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class HubspotUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotUpdateContactConnector implements ConnectorInterface
{

    /**
     * @var HubspotSystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * HubspotSyncConnector constructor.
     *
     * @param HubspotSystem     $system
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     */
    public function __construct(HubspotSystem $system, DocumentManager $dm, CurlSenderFactory $factory)
    {
        $this->system                  = $system;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hubspot-update-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Hubspot has no support for action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $arr = json_decode($dto->getData(), TRUE);

        if (!is_array($arr) || empty($arr)) {
            throw new CleverConnectorsException(
                'Empty data or bad format.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if (!array_key_exists('subscriptionType', $arr)) {
            throw new CleverConnectorsException(
                'Missing "subscriptionType" key in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        // todo make request a/sync?

        switch ($arr['subscriptionType']) {
            case 'contact.creation':
                $dto = $this->processCreation($dto, $arr);
                break;
            case 'contact.propertyChange':
                $dto = $this->processPropertyChange($dto, $arr);
                break;
            case 'contact.deletion':
                $dto = $this->processDeletion($dto, $arr);
                break;
            default:
                throw new CleverConnectorsException(
                    sprintf('Unknown subscription type "%s"', $arr['subscriptionType']),
                    CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE
                );
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     * @param array      $body
     *
     * @return ProcessDto
     */
    private function processCreation(ProcessDto $dto, array $body): ProcessDto
    {
        // todo make request to get the whole entity because we need email
    }

    /**
     * @param ProcessDto $dto
     * @param array      $body
     *
     * @return ProcessDto
     */
    private function processPropertyChange(ProcessDto $dto, array $body): ProcessDto
    {
        // todo make request to get the whole entity because we need email
    }

    /**
     * @param ProcessDto $dto
     * @param array      $body
     *
     * @return ProcessDto
     */
    private function processDeletion(ProcessDto $dto, array $body): ProcessDto
    {
        // todo make request to get the whole entity because we need email
    }

}