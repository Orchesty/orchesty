<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;

/**
 * Class FacebookaudienceUpdateAdstateConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceUpdateAdstateConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '/api-demo/fb/%s/ad/%s/update/state';

    /**
     * @var string
     */
    private $aimUrl;

    /**
     * FacebookaudienceUpdateAdstateConnector constructor.
     *
     * @param FacebookaudienceSystem $system
     * @param DocumentManager        $dm
     * @param CurlManagerInterface   $manager
     * @param string                 $aimUrl
     */
    public function __construct(
        FacebookaudienceSystem $system,
        DocumentManager $dm,
        CurlManagerInterface $manager,
        string $aimUrl
    )
    {
        parent::__construct($system, $dm, $manager);
        $this->aimUrl = rtrim($aimUrl, '/');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-update-adstate-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        $sysInst = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req     = new RequestDto(CurlManager::METHOD_POST,
            new Uri(sprintf($this->aimUrl . self::URL, $data['client_id'], $data['id']))
        );
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $req->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->setBody(json_encode([
            'status' => 'PENDING_REVIEW',
            'ref_id' => $data['ad_id'],
        ]));

        try {
            $this->manager->send($req);
        } catch (CurlException $e) {
            $this->connectorError($e, $this->system, $sysInst, $dto);
        }

        return $dto;
    }

}