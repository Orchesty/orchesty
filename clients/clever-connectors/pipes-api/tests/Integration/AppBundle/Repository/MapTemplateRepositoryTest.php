<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Repository\MapTemplateRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class MapTemplateRepositoryTest
 *
 * @package Tests\Integration\AppBundle\Repository
 */
final class MapTemplateRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers MapTemplateRepository::findUnique()
     */
    public function testFindUnique(): void
    {
        /** @var MapTemplateRepository $repo */
        $repo = $this->dm->getRepository(MapTemplate::class);

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user')
            ->setSystem('sys')
            ->setToken('tok');
        $this->dm->persist($systemInstall);

        $systemInstall2 = new SystemInstall();
        $systemInstall2
            ->setUser('user')
            ->setSystem('sys')
            ->setToken('tok');
        $this->dm->persist($systemInstall2);

        $mapTemplate = new MapTemplate();
        $mapTemplate
            ->setAction(new DataLayoutActionEnum(DataLayoutActionEnum::SUBSCRIBER))
            ->setDirection(MapTemplate::DIRECTION_IN)
            ->setSystemInstall($systemInstall);
        $this->dm->persist($mapTemplate);

        $this->dm->flush();

        $result = $repo->findUnique(
            $systemInstall,
            new DataLayoutActionEnum(DataLayoutActionEnum::SUBSCRIBER),
            MapTemplate::DIRECTION_IN
        );

        $this->assertNotNull($result);

        $result = $repo->findUnique(
            $systemInstall2,
            new DataLayoutActionEnum(DataLayoutActionEnum::SUBSCRIBER),
            MapTemplate::DIRECTION_IN
        );

        $this->assertNull($result);

        $result = $repo->findUnique(
            $systemInstall,
            new DataLayoutActionEnum(DataLayoutActionEnum::CAMPAIGN),
            MapTemplate::DIRECTION_IN
        );

        $this->assertNull($result);

        $result = $repo->findUnique(
            $systemInstall,
            new DataLayoutActionEnum(DataLayoutActionEnum::SUBSCRIBER),
            MapTemplate::DIRECTION_OUT
        );

        $this->assertNull($result);
    }

}