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
 * Class FacebookaudienceCreateAdsetConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceCreateAdsetConnector extends FacebookaudienceConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-create-adset-connector';
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

        if (array_key_exists('adset_id', $data)) {
            return $dto;
        }

        $sysInst = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req     = $this->system->getRequestDto($sysInst, 'POST');
        $req->setUri(new Uri(sprintf('%s/act_%s/adsets', $req->getUri(TRUE),
            $sysInst->getSettings()[FacebookaudienceSystem::AD_ACCOUNT])));
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $opt = [
            'form_params' => [
                'access_token'      => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
                'name'              => $data['name'],
                'campaign_id'       => $data['campaign_id'],
                'billing_event'     => $data['billing_event'],
                'optimization_goal' => $data['billing_event'],
                'bid_amount'        => $data['bid_amount'],
                'daily_budget'      => $data['daily_budget'],
                'promoted_object'   => json_encode([
                    'page_id' => $data['page_id'],
                ]),
                'targeting'         => json_encode([
                    'custom_audiences'    => [$data['audience_id']],
                    'publisher_platforms' => ['facebook'],
                ]),
            ],
        ];

        try {
            $res              = $this->manager->send($req, $opt);
            $data['adset_id'] = json_decode($res->getBody(), TRUE)['id'];
            unset($data['campaign_id']);
            unset($data['billing_event']);
            unset($data['bid_amount']);
            unset($data['daily_budget']);
            unset($data['audience_id']);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $sysInst, $this->system, $dto);
        }

        return $dto->setData(json_encode($data));
    }

}