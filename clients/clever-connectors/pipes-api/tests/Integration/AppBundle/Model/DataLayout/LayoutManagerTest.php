<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\DataLayout;

use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\DataLayout\Exceptions\LayoutException;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutManager;
use CleverConnectors\AppBundle\Repository\DataLayoutRepository;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class LayoutManagerTest
 *
 * @package Tests\Integration\AppBundle\Model\DataLayout
 */
final class LayoutManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var LayoutManager
     */
    private $manager;

    /**
     * @var ObjectRepository|DataLayoutRepository
     */
    private $repository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->manager    = $this->container->get('cc.layout.manager');
        $this->repository = $this->dm->getRepository(DataLayout::class);
    }

    /**
     * @covers LayoutManager::removeBySystemInstall()
     */
    public function testRemoveBySystemInstall(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSystem('null.user.group');
        $this->persistAndFlush($systemInstall);

        $action = TopologyNameUtils::getTopologyName(
            TopologyNameUtils::UPDATED_SUBSCRIBERS,
            $systemInstall->getSystem()
        );

        $this->manager->createDataLayout($systemInstall, [
            'action' => $action,
            'fields' => [
                ['key' => 'key-text', 'type' => TypeEnum::TEXT],
            ],
        ]);

        $this->assertCount(1, $this->repository->findBy(['systemInstall' => $systemInstall->getId()]));

        $this->manager->removeBySystemInstall($systemInstall);

        $this->dm->clear();

        $this->assertCount(0, $this->repository->findBy(['systemInstall' => $systemInstall->getId()]));
    }

    /**
     *
     */
    public function testCreateDataLayout(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSystem('null.user.group');
        $this->persistAndFlush($systemInstall);

        $action = TopologyNameUtils::getTopologyName(
            TopologyNameUtils::UPDATED_SUBSCRIBERS,
            $systemInstall->getSystem()
        );

        $this->manager->createDataLayout($systemInstall, [
            'action' => $action,
            'fields' => [
                ['key' => 'key-text', 'type' => TypeEnum::TEXT],
                ['key' => 'key-date', 'type' => TypeEnum::DATE],
                ['key' => 'key-bool', 'type' => TypeEnum::BOOL],
            ],
        ]);

        $this->dm->clear();
        /** @var DataLayout $dataLayout */
        $dataLayout = $this->repository->findOneBy([
            'systemInstall' => $systemInstall->getId(),
            'action'        => $action,
        ]);

        $this->assertInstanceOf(DataLayout::class, $dataLayout);
        $this->assertEquals([
            '_id'    => $dataLayout->getId(),
            'action' => $action,
            'fields' => [
                0 => [
                    'key'  => 'key-text',
                    'type' => 'text',
                ],
                1 => [
                    'key'  => 'key-date',
                    'type' => 'date',
                ],
                2 => [
                    'key'  => 'key-bool',
                    'type' => 'bool',
                ],
            ],
        ], $dataLayout->toArray());

        $this->expectException(LayoutException::class);
        $this->expectExceptionCode(LayoutException::DATA_LAYOUT_ALREADY_EXISTS);

        $this->manager->createDataLayout($systemInstall, [
            'action' => $action,
            'fields' => [],
        ]);
    }

    /**
     *
     */
    public function testCreateFailed(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSystem('shoptet');
        $this->persistAndFlush($systemInstall);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::DYNAMIC_MAPPING_NOT_ALLOWED);

        $action = TopologyNameUtils::getTopologyName(
            TopologyNameUtils::UPDATED_SUBSCRIBERS,
            $systemInstall->getSystem()
        );

        $this->manager->createDataLayout($systemInstall, [
            'action' => $action,
            'fields' => [
                ['key' => 'key-text', 'type' => TypeEnum::TEXT],
                ['key' => 'key-date', 'type' => TypeEnum::DATE],
                ['key' => 'key-bool', 'type' => TypeEnum::BOOL],
            ],
        ]);
    }

    /**
     *
     */
    public function testUpdateDataLayout(): void
    {
        $dataLayout = $this->prepareDataLayout();

        $this->manager->updateDataLayout($dataLayout, [
            'fields' => [
                ['key' => 'key-text-update', 'type' => TypeEnum::TEXT],
                ['key' => 'key-date-update', 'type' => TypeEnum::DATE],
                ['key' => 'key-bool-update', 'type' => TypeEnum::BOOL],
            ],
        ]);

        $this->dm->clear();
        /** @var DataLayout $dataLayout */
        $dataLayout = $this->repository->find($dataLayout->getId());
        $this->assertInstanceOf(DataLayout::class, $dataLayout);
        $this->assertEquals([
            '_id'    => $dataLayout->getId(),
            'action' => $dataLayout->getAction(),
            'fields' => [
                0 => [
                    'key'  => 'key-text-update',
                    'type' => 'text',
                ],
                1 => [
                    'key'  => 'key-date-update',
                    'type' => 'date',
                ],
                2 => [
                    'key'  => 'key-bool-update',
                    'type' => 'bool',
                ],
            ],
        ], $dataLayout->toArray());
    }

    /**
     *
     */
    public function testDeleteDataLayout(): void
    {
        $dataLayout = $this->prepareDataLayout();
        $this->manager->deleteDataLayout($dataLayout);
        $this->assertNull($this->repository->find($dataLayout->getId()));
    }

    /**
     * @return DataLayout
     */
    private function prepareDataLayout(): DataLayout
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSystem('null.user.group');
        $this->persistAndFlush($systemInstall);

        $action = TopologyNameUtils::getTopologyName(
            TopologyNameUtils::UPDATED_SUBSCRIBERS,
            $systemInstall->getSystem()
        );

        $this->manager->createDataLayout($systemInstall, [
            'action' => $action,
            'fields' => [
                ['key' => 'key-text', 'type' => TypeEnum::TEXT],
                ['key' => 'key-date', 'type' => TypeEnum::DATE],
                ['key' => 'key-bool', 'type' => TypeEnum::BOOL],
            ],
        ]);

        /** @var DataLayout $dataLayout */
        $dataLayout = $this->repository->findOneBy([
            'systemInstall' => $systemInstall->getId(),
            'action'        => $action,
        ]);

        return $dataLayout;
    }

}