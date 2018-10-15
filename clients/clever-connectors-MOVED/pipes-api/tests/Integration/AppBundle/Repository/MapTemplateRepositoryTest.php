<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Repository\MapTemplateRepository;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
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
            ->setSystem('null.user.group')
            ->setToken('tok');
        $this->dm->persist($systemInstall);

        $systemInstall2 = new SystemInstall();
        $systemInstall2
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('tok');
        $this->dm->persist($systemInstall2);

        $dto = new ActionDto(
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $systemInstall->getSystem()),
            MapTemplate::DIRECTION_IN
        );

        $mapTemplate = new MapTemplate();
        $mapTemplate
            ->setAction($dto)
            ->setDirection($dto)
            ->setSystemInstall($systemInstall);
        $this->dm->persist($mapTemplate);

        $this->dm->flush();

        $result = $repo->findUnique($systemInstall, $dto);

        $this->assertNotNull($result);

        $result = $repo->findUnique($systemInstall2, $dto);

        $this->assertNull($result);

        $dto2   = new ActionDto(
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATE_CONTACT, $systemInstall->getSystem()),
            MapTemplate::DIRECTION_IN
        );
        $result = $repo->findUnique($systemInstall, $dto2);

        $this->assertNull($result);

        $dto3   = new ActionDto(
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $systemInstall->getSystem()),
            MapTemplate::DIRECTION_OUT
        );
        $result = $repo->findUnique($systemInstall, $dto3);

        $this->assertNull($result);
    }

}