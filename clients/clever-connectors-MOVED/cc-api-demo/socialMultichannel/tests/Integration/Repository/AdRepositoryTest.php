<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use CleverCore\SocialMultichannel\Entities\Ad;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Repositories\AdRepository;
use Doctrine\ORM\ORMException;
use Exception;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AdRepositoryTest
 *
 * @package Tests\Integration\Repository
 */
final class AdRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetUnprocessed(): void
    {
        /** @var AdRepository $repo */
        $repo = $this->em->getRepository(Ad::class);

        $this->prepAd('cli', ['status' => 'active'], AdTypeEnum::INSTAGRAM);
        $this->prepAd('cli2', ['status' => 'active']);
        $ad = $this->prepAd('cli', ['status' => 'active']);

        $ads = $repo->getUnprocessed('cli', AdTypeEnum::FB);
        self::assertEquals(1, count($ads));
        self::assertEquals($ad->toArray(), $ads[0]);
    }

    /**
     * @throws Exception
     */
    public function testGetById(): void
    {
        /** @var AdRepository $repo */
        $repo = $this->em->getRepository(Ad::class);

        $ad = $this->prepAd();
        self::assertInstanceOf(Ad::class, $repo->getById($ad->getId(), 'cli'));

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage(sprintf(
            'Ad with given id [%s] does not exist or client [ccc] is not an owner.',
            $ad->getId()));
        $repo->getById($ad->getId(), 'ccc');
    }

    /**
     * @param string $clientId
     * @param array  $sett
     * @param string $type
     *
     * @return Ad
     */
    private function prepAd(string $clientId = 'cli', array $sett = [], string $type = AdTypeEnum::FB): Ad
    {
        $ad = new Ad();
        $ad->setClientId($clientId)
            ->setAdType($type)
            ->setAudienceMirrorId('')
            ->setSettings($sett);
        $this->persistAndFlushEntity($ad);

        return $ad;
    }

}