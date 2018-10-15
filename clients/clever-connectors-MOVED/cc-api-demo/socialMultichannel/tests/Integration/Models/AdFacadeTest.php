<?php declare(strict_types=1);

namespace Tests\Integration\Models;

use CcApi\Curl\CurlSender;
use CleverCore\SocialMultichannel\DI\SocialMultichannelExtension;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use CleverCore\SocialMultichannel\Models\AdFacade;
use CleverCore\SocialMultichannel\Models\PipesSender;
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
     * @covers PipesSender::createAd()
     * @covers PipesSender::deleteAd()
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
            new TestAdModule($this->em, $this->mockSender())
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
            'status' => 'PAUSED',
        ], $ad->getSettings());
        self::assertEquals(AdTypeEnum::FB, $ad->getAdType());

        $facade->updateAd($ad, ['status' => 'ACTIVE']);
        self::assertEquals([
            'status' => 'ACTIVE',
        ], $ad->getSettings());

        $id = $ad->getId();
        $facade->deleteAd($ad);

        self::assertNull($this->em->find(Ad::class, $id));
    }

    /**
     * @return PipesSender
     * @throws ReflectionException
     */
    private function mockSender(): PipesSender
    {
        /** @var CurlSender|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlSender::class);
        $curl->expects($this->at(0))
            ->method('send')->willReturnCallback(
                function (Request $req): ResponseInterface {
                    self::assertEquals('http://backend/system/fc/user/cli/action/createAudience', $req->getUri());
                    self::assertEquals([
                        'id'                 => '1',
                        'name'               => 'Nae',
                        'adset_id'           => 'adset',
                        'status'             => 'PAUSED',
                        'page_id'            => 'page',
                        'campaign_objective' => 'LINK_CLICKS',
                        'ad_data'            => [],
                        'distribution_list'  => 'crs',
                        'audience'           => [
                            'id'        => '1',
                            'name'      => 'audi',
                            'source'    => 'list',
                            'client_id' => 'cl',
                        ],
                        'type'               => 'fb',
                    ], json_decode($req->getBody()->getContents(), TRUE));

                    return new Response();
                }
            );
        $curl->expects($this->at(1))
            ->method('send')->willReturnCallback(
                function (Request $req): ResponseInterface {
                    self::assertEquals('http://backend/system/fc/user/cli/action/deleteAd', $req->getUri());
                    self::assertEquals([
                        'mirror_id' => '',
                        'ad_id'     => '1',
                    ], json_decode($req->getBody()->getContents(), TRUE));

                    return new Response();
                }
            );

        return new PipesSender('http://backend/', $curl);
    }

}