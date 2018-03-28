<?php declare(strict_types=1);

namespace Tests\Integration\Models;

use CleverCore\SocialMultichannel\Documents\AudienceMirror;
use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use CleverCore\SocialMultichannel\Models\AdFacade;
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
     */
    public function testAd(): void
    {
        if ($this->container->hasService('module.fb')) {
            $this->container->removeService('module.fb');
        }
        $this->container->addService('module.fb', new TestAdModule($this->em));

        $aud = new Audience();
        $aud->setName('audi')
            ->setSourceType(AudienceSourceEnum::LIST)
            ->setClientId('cl');
        $this->persistAndFlushEntity($aud);

        /** @var AdFacade $facade */
        $facade = $this->container->getByType(AdFacade::class);
        $ad = $facade->createAd($aud, AdTypeEnum::FB, ['Nae' => 'Gao']);

        self::assertEquals(['Nae' => 'Gao'], $ad->getSettings());
        self::assertEquals(AdTypeEnum::FB, $ad->getAdType());

        $facade->updateAd($ad, ['Shi' => 'nne']);
        self::assertEquals(['Shi' => 'nne'], $ad->getSettings());

        self::assertInstanceOf(AudienceMirror::class, $this->dm->find(AudienceMirror::class, $ad->getAudienceMirrorId()));

        $id = $ad->getId();
        $facade->deleteAd($ad);

        self::assertNull($this->em->find(Ad::class, $id));
        self::assertNull($this->dm->find(AudienceMirror::class, $ad->getAudienceMirrorId()));
    }

}