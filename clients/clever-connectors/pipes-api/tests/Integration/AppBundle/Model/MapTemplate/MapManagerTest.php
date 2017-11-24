<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\MapTemplate;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\MapTemplate\MapManager;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class MapManagerTest
 *
 * @package Tests\Integration\AppBundle\Model\MapTemplate
 */
final class MapManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var MapManager
     */
    private $manager;

    /**
     *
     */
    public function setUp(): void
    {
        $this->dm->getConnection()->dropDatabase('clever-connectors');
        $this->manager = $this->container->get('cc.map_template.manager');
    }

    /**
     * @covers MapManager::removeBySystemInstall()
     */
    public function testRemoveBySystemInstall(): void
    {
        $systemInstall = $this->prepareSystemInstall();

        $dto = new ActionDto(
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $systemInstall->getSystem()),
            MapTemplate::DIRECTION_IN
        );

        $map = (new MapTemplate())
            ->setAction($dto)
            ->setDirection($dto)
            ->setSystemInstall($systemInstall);
        $this->persistAndFlush($map);

        $this->assertCount(1,
            $this->dm->getRepository(MapTemplate::class)->findBy(['systemInstall' => $systemInstall->getId()]));

        $this->manager->removeBySystemInstall($systemInstall);

        $this->dm->clear();

        $this->assertCount(0,
            $this->dm->getRepository(MapTemplate::class)->findBy(['systemInstall' => $systemInstall->getId()]));
    }

    /**
     * @covers MapManager::create()
     */
    public function testCreate(): void
    {
        $systemInstall = $this->prepareSystemInstall();
        $data          = $this->getData();

        $this->assertCount(0, $this->dm->getRepository(MapTemplate::class)->findAll());

        $result = $this->manager->create($systemInstall, $data);

        $this->assertResult($result, $data, $systemInstall);
    }

    /**
     * @covers MapManager::create()
     */
    public function testCreateFailed(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSystem('shoptet');
        $this->persistAndFlush($systemInstall);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::DYNAMIC_MAPPING_NOT_ALLOWED);

        $this->manager->create($systemInstall, $this->getData());
    }

    /**
     * @covers MapManager::create()
     * @covers MapManager::update()
     */
    public function testCreate2Update(): void
    {
        $systemInstall = $this->prepareSystemInstall();

        $this->assertCount(0, $this->dm->getRepository(MapTemplate::class)->findAll());

        $this->manager->create($systemInstall, $this->getData());

        $this->assertCount(1, $this->dm->getRepository(MapTemplate::class)->findAll());

        $data   = $this->getDataCreate2Update();
        $result = $this->manager->create($systemInstall, $data);

        $this->assertResult($result, $data, $systemInstall);
    }

    /**
     * @covers MapManager::update()
     * @covers MapManager::delete()
     */
    public function testUpdate(): void
    {
        $systemInstall = $this->prepareSystemInstall();

        $this->assertCount(0, $this->dm->getRepository(MapTemplate::class)->findAll());

        $result = $this->manager->create($systemInstall, $this->getData());

        $this->assertCount(1, $this->dm->getRepository(MapTemplate::class)->findAll());

        $data   = $this->getDataUpdate();
        $result = $this->manager->update($result, $data);

        $this->assertResult($result, $data, $systemInstall, TRUE);

        $this->manager->delete($result);

        $this->assertCount(0, $this->dm->getRepository(MapTemplate::class)->findAll());
    }

    /**
     * @param MapTemplate   $result
     * @param array         $data
     * @param SystemInstall $systemInstall
     * @param bool          $assertOnlyFields
     */
    private function assertResult(
        MapTemplate $result,
        array $data,
        SystemInstall $systemInstall,
        bool $assertOnlyFields = FALSE
    ): void
    {
        $this->assertInstanceOf(MapTemplate::class, $result);
        $this->assertCount(1, $this->dm->getRepository(MapTemplate::class)->findAll());

        if (!$assertOnlyFields) {
            $this->assertEquals($data['action'], $result->getAction());
            $this->assertEquals($data['direction'], $result->getDirection());
            $this->assertEquals($systemInstall->getId(), $result->getSystemInstall());
        }

        $fields = $result->getFields();
        $this->assertEquals(count($data['fields']), count($fields));
        $this->assertEquals($data['fields'][0]['name'], $fields[0]->getName());
        $this->assertEquals($data['fields'][0]['type'], $fields[0]->getType());

        $items = $fields[0]->getItems();
        $this->assertEquals(count($data['fields'][0]['items']), count($items));
        $this->assertEquals($data['fields'][0]['items'][0], $items[0]);
        $this->assertEquals($data['fields'][0]['items'][1], $items[1]);
    }

    /**
     * @return SystemInstall
     */
    private function prepareSystemInstall(): SystemInstall
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSystem('null.user.group')
            ->setUser('user')
            ->setToken('tok');

        $this->dm->persist($systemInstall);
        $this->dm->flush();

        return $systemInstall;
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        $action = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, 'null.user.group');

        return [
            'action'    => $action,
            'direction' => MapTemplate::DIRECTION_IN,
            'fields'    => [
                [
                    'name'  => 'abc',
                    'key'   => 'abc',
                    'type'  => TypeEnum::TEXT,
                    'items' => ['def', 'ghi'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getDataCreate2Update(): array
    {
        $action = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, 'null.user.group');

        return [
            'action'    => $action,
            'direction' => MapTemplate::DIRECTION_IN,
            'fields'    => [
                [
                    'key'   => 'aaa',
                    'name'  => 'aaa',
                    'type'  => TypeEnum::URL,
                    'items' => ['bbb', 'ccc'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getDataUpdate(): array
    {
        return [
            'fields' => [
                [
                    'key'   => 'aaa',
                    'name'  => 'aaa',
                    'type'  => TypeEnum::URL,
                    'items' => ['bbb', 'ccc'],
                ],
            ],
        ];
    }

}