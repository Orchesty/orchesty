<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class FacebookaudienceRunCreateAdActionConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceRunCreateAdActionConnector implements CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL = '/system/facebookaudience/user/%s/action/createAd';

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * @var SystemInterface
     */
    private $system;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $backend;

    /**
     * FacebookaudienceRunCreateAdActionConnector constructor.
     *
     * @param CurlManager     $curl
     * @param SystemInterface $system
     * @param DocumentManager $dm
     * @param string          $backend
     */
    public function __construct(CurlManager $curl, SystemInterface $system, DocumentManager $dm, string $backend)
    {
        $this->curl    = $curl;
        $this->system  = $system;
        $this->dm      = $dm;
        $this->logger  = new NullLogger();
        $this->backend = rtrim($backend, '/');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        if (CMHeaders::get('createAd', $dto->getHeaders())) {
            $data = json_decode($dto->getData(), TRUE);
            $req  = new RequestDto(CurlManager::METHOD_POST, new Uri(sprintf(
                $this->backend . self::URL, $data['user_id']
            )));
            $req->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->setBody($dto->getData());

            try {
                $this->curl->send($req);
            } catch (CurlException $e) {
                /** @var SystemInstallRepository $repo */
                $repo = $this->dm->getRepository(SystemInstall::class);
                $this->logError($e->getCode(), $this->system, $repo->getSystemInstallFromHeaders($dto->getHeaders()));
            }
        }

        return $dto;
    }

}