<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\LoggerUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Class FacebookaudienceConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
abstract class FacebookaudienceConnectorAbstract implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var FacebookaudienceSystem
     */
    protected $system;

    /**
     * @var CurlManagerInterface
     */
    protected $manager;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * FacebookaudienceGetAudiencesConnector constructor.
     *
     * @param FacebookaudienceSystem $system
     * @param DocumentManager        $dm
     * @param CurlManagerInterface   $manager
     */
    public function __construct(FacebookaudienceSystem $system, DocumentManager $dm, CurlManagerInterface $manager)
    {
        $this->system                  = $system;
        $this->manager                 = $manager;
        $this->dm                      = $dm;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Facebook Audience has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH
        );
    }

    /**
     * @param CurlException $exception
     * @param SystemInstall $systemInstall
     */
    protected function logCurlException(CurlException $exception, SystemInstall $systemInstall): void
    {
        $response = $exception->getResponse();
        if (isset($response) && $response->getStatusCode() == 400) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, TRUE);
            if (isset($data['error']['code']) && $data['error']['code'] == 190) {
                $this->logger->info(
                    NotificationTypeEnum::ACCESS_EXPIRATION,
                    LoggerUtils::getMessage($this->system, $systemInstall)
                );
            }
        }
        if (isset($response) && $response->getStatusCode() == 500) {
            $this->logger->info(
                NotificationTypeEnum::SERVICE_UNAVAILABLE,
                LoggerUtils::getMessage($this->system, $systemInstall)
            );
        }
    }

}