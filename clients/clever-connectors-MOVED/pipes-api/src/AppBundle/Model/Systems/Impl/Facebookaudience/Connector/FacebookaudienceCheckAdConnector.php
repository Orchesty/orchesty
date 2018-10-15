<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceCheckAdConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceCheckAdConnector extends FacebookaudienceConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-check-ad-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        $sys  = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req  = $this->system->getRequestDto($sys, CurlManager::METHOD_POST);
        $req->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);
        $req->setUri(new Uri(sprintf('%s/%s', $req->getUri(TRUE), $data['ref_id'])));
        $req->setBody(json_encode([
            'fields'       => 'status',
            'access_token' => $sys->getSettings()[OAuth2Provider::ACCESS_TOKEN],
        ]));

        try {
            $res            = $this->manager->send($req);
            $body           = json_decode($res->getBody(), TRUE);
            $data['status'] = $body['status'];

            $dto->setData(json_encode($data));
        } catch (CurlException $e) {
            $this->logConnectorError($e, $sys, $this->system, $dto);
        }

        return $dto;
    }

}