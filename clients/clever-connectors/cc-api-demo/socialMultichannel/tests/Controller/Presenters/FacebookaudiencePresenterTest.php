<?php declare(strict_types=1);

namespace Tests\Controller\Presenters;

use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use Exception;
use Tests\ControllerTestCaseAbstract;

/**
 * Class FacebookaudiencePresenterTest
 *
 * @package Tests\Controller\Presenters
 */
final class FacebookaudiencePresenterTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testUpdateStatus(): void
    {
        $ad = $this->prepAd();

        $this->sendJsonRequest(
            'SocialMultichannel:Facebookaudience',
            'updateAdStatus',
            'POST',
            [
                'clientId' => 'cli',
                'adId'     => $ad->getId(),
            ],
            [
                'ref_id' => 'someID',
                'status' => 'ACTIVE',
            ]
        );

        $repo = $this->em->getRepository(Ad::class);
        /** @var Ad $ad */
        $ad   = $repo->find($ad->getId());
        self::assertEquals('someID', $ad->getRefId());
        self::assertEquals('ACTIVE', $ad->getSettings()['status']);
    }

    /**
     * @throws Exception
     */
    public function testGetUnprocessed(): void
    {
        $ad = $this->prepAd();

        $res = $this->sendJsonRequest(
            'SocialMultichannel:Facebookaudience',
            'getUnprocessed',
            'POST',
            ['clientId' => 'cli'],
            []
        );

        self::assertEquals(1, count($res));
        self::assertEquals($ad->toArray(), $res[0]);
    }

    /**
     * @return Ad
     */
    private function prepAd(): Ad
    {
        $ad = new Ad();
        $ad->setSettings(['status' => 'asd'])
            ->setAudienceMirrorId('')
            ->setClientId('cli')
            ->setAdType(AdTypeEnum::FB);
        $this->persistAndFlushEntity($ad);

        return $ad;
    }

}