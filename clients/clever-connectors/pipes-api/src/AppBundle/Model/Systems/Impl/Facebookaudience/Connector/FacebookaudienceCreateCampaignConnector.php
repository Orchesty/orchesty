<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;

/**
 * Class FacebookaudienceCreateCampaignConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceCreateCampaignConnector extends FacebookaudienceConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-create-campaign-connector';
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

        if (array_key_exists('campaign_id', $data)) {
            return $dto;
        }

        $sysInst = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req     = $this->system->getRequestDto($sysInst, 'POST');
        $req->setUri(new Uri(sprintf('%s/act_%s/campaigns', $req->getUri(TRUE),
            $sysInst->getSettings()[FacebookaudienceSystem::AD_ACCOUNT])));
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $opt = [
            'form_params' => [
                'access_token' => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
                'name'         => $data['name'],
                'status'       => 'ACTIVE',
                'objective'    => $data['campaign_objective'],
            ],
        ];

        try {
            $res                 = $this->manager->send($req, $opt);
            $data['campaign_id'] = json_decode($res->getBody(), TRUE)['id'];
            unset($data['campaign_objective']);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $sysInst, $this->system, $dto);
        }

        return $dto->setData(json_encode($data));
    }

}