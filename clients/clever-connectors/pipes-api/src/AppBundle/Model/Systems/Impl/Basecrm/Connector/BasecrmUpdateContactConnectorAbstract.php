<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class BasecrmUpdateContactConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
abstract class BasecrmUpdateContactConnectorAbstract implements ConnectorInterface
{

    /**
     * @var BasecrmSystem
     */
    protected $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * @var CurlManagerInterface
     */
    protected $curl;

    /**
     * BasecrmCreateContactConnector constructor.
     *
     * @param BasecrmSystem        $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     */
    function __construct(BasecrmSystem $system, DocumentManager $dm, CurlManagerInterface $curl)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->curl                    = $curl;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('BaseCRM has no support for event, updateContactConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
    }

}