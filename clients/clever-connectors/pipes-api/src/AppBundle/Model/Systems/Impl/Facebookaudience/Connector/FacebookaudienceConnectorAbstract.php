<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class FacebookaudienceConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
abstract class FacebookaudienceConnectorAbstract implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

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
     * 4 - application request limit reached
     * 17 - user request limit reached
     * 100 - invalid parameter
     * 190 - invalid access token
     *
     * @param CurlException $exception
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    protected function logConnectorError(
        CurlException $exception,
        SystemInstall $systemInstall,
        ?ProcessDto $dto = NULL
    ): ?ProcessDto
    {
        $response = $exception->getResponse();

        if (isset($response)) {
            $httpCode = $response->getStatusCode();
            if ($response->getStatusCode() == 400) {
                $data      = json_decode($response->getBody()->getContents(), TRUE);
                $errorCode = isset($data['error']['code']) ? $data['error']['code'] : NULL;
                if (in_array($errorCode, [4, 17])) {
                    if ($dto) {
                        return HeadersUtils::setLimitHeaderToDto($dto);
                    } else {
                        $httpCode = 429;
                    }
                } elseif ($errorCode == 100) {
                    $httpCode = 400;
                } elseif ($errorCode == 190) {
                    $httpCode = 401;
                } else {
                    $httpCode = 500;
                }
            }

            $this->logError($httpCode, $this->system, $systemInstall);
        }

        throw $exception;
    }

}