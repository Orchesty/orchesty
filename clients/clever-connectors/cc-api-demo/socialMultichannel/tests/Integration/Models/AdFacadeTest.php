<?php declare(strict_types=1);

namespace Tests\Integration\Models;

use CcApi\Curl\CurlSender;
use CleverCore\SocialMultichannel\DI\SocialMultichannelExtension;
use CleverCore\SocialMultichannel\Documents\AudienceMirror;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use CleverCore\SocialMultichannel\Models\AdFacade;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AdFacadeTest
 *
 * @package Tests\Integration\Models
 */
final class AdFacadeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers AdFacade::createAd()
     * @covers AdFacade::updateAd()
     * @covers AdFacade::deleteAd()
     * @covers AdModuleAbstract::createAd()
     * @covers AdModuleAbstract::updateAd()
     * @covers AdModuleAbstract::deleteAd()
     *
     * @throws Exception
     */
    public function testAd(): void
    {
        if ($this->container->hasService('module.fb')) {
            $this->container->removeService('module.fb');
        }
        $this->container->addService(
            SocialMultichannelExtension::NAME . '.module.fb',
            new TestAdModule('http://backend/',
                $this->em, $this->mockCurl())
        );

        $aud = new Audience();
        $aud->setName('audi')
            ->setSourceType(AudienceSourceEnum::LIST)
            ->setClientId('cl');
        $this->persistAndFlushEntity($aud);

        /** @var AdFacade $facade */
        $facade = $this->container->getByType(AdFacade::class);
        $ad     = $facade->createAd($aud, AdTypeEnum::FB, [
            'name'               => 'Nae',
            'page_id'            => 'page',
            'ad_data'            => [],
            'campaign_objective' => 'LINK_CLICKS',
            'distribution_list'  => 'crs',
            'adset_id'           => 'adset',
        ]);

        self::assertEquals([
            'name'               => 'Nae',
            'page_id'            => 'page',
            'ad_data'            => [],
            'campaign_objective' => 'LINK_CLICKS',
            'distribution_list'  => 'crs',
            'adset_id'           => 'adset',
            'status'             => 'PAUSED',
        ], $ad->getSettings());
        self::assertEquals(AdTypeEnum::FB, $ad->getAdType());

        $facade->updateAd($ad, ['status' => 'ACTIVE']);
        self::assertEquals([
            'name'               => 'Nae',
            'page_id'            => 'page',
            'ad_data'            => [],
            'campaign_objective' => 'LINK_CLICKS',
            'distribution_list'  => 'crs',
            'adset_id'           => 'adset',
            'status'             => 'ACTIVE',
        ], $ad->getSettings());

        self::assertInstanceOf(AudienceMirror::class,
            $this->dm->find(AudienceMirror::class, $ad->getAudienceMirrorId()));

        $id = $ad->getId();
        $facade->deleteAd($ad);

        self::assertNull($this->em->find(Ad::class, $id));
        self::assertNull($this->dm->find(AudienceMirror::class, $ad->getAudienceMirrorId()));
    }

    /**
     * @return CurlSender
     * @throws ReflectionException
     */
    private function mockCurl(): CurlSender
    {
        /** @var CurlSender|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlSender::class);
        $curl->expects($this->at(0))
            ->method('send')->willReturnCallback(
                function (Request $req): ResponseInterface {
                    self::assertEquals('http://backend/system/fc/user/usr/action/create_ad', $req->getUri());
                    self::assertEquals([
                        'id'       => '1',
                        'settings' => [
                            'name'               => 'Nae',
                            'adset_id'           => 'adset',
                            'status'             => 'PAUSED',
                            'page_id'            => 'page',
                            'campaign_objective' => 'LINK_CLICKS',
                            'ad_data'            => [],
                            'distribution_list'  => 'crs',
                        ],
                        'type'     => 'fb',
                    ], json_decode($req->getBody()->getContents(), TRUE));

                    return new Response();
                }
            );

        return $curl;
    }

}