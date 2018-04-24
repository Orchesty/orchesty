<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceCreateAdConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceCreateAdConnector extends FacebookaudienceConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-create-ad-connector';
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

        if (array_key_exists('creative_id', $data)) {
            return $dto;
        }

        $sysInst = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req     = $this->system->getRequestDto($sysInst, 'POST');
        $req->setUri(new Uri(sprintf('%s/act_%s/ads', $req->getUri(TRUE),
            $sysInst->getSettings()[FacebookaudienceSystem::AD_ACCOUNT])));
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $ad = count($data['ad_data']) > 1
            ? $this->createCarouselAdset($data)
            : $this->createSingleImageAdset($data);

        $opt = [
            'form_params' => [
                'access_token' => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
                'name'         => $data['name'],
                'status'       => $data['status'],
                'adset_id'     => $data['adset_id'],
                'creative'     => $ad,
            ],
        ];

        try {
            $res  = $this->manager->send($req, $opt);
            $data = [
                'ad_id'     => json_decode($res->getBody(), TRUE)['id'],
                'client_id' => $data['client_id'],
                'mirror_id' => $data['mirror_id'],
                'id'        => $data['id'],
            ];
        } catch (CurlException $e) {
            $this->logConnectorError($e, $sysInst, $this->system, $dto);
        }

        return $dto->setData(json_encode($data));
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function createSingleImageAdset(array $data): array
    {
        return [
            'title'             => $data['ad_data'][0]['title'],
            'body'              => $data['ad_data'][0]['description'],
            'object_story_spec' => [
                'link_data' => [
                    'image_hash' => $data['ad_data'][0]['image_hash'],
                    'link'       => $data['ad_data'][0]['link'],
                    'message'    => $data['ad_data'][0]['description'],
                ],
                'page_id'   => $data['page_id'],
            ],
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function createCarouselAdset(array $data): array
    {
        $carusel = [];
        foreach ($data['ad_data'] as $item) {
            $carusel[] = [
                'image_hash'  => $item['image_hash'],
                'link'        => $item['link'],
                'name'        => $item['title'],
                'description' => $item['description'],
            ];
        }

        return [
            'object_story_spec' => [
                'link_data' => [
                    'child_attachments' => $carusel,
                    'link'              => $data['ad_data'][0]['link'],
                ],
                'page_id'   => $data['page_id'],
            ],
        ];
    }

}