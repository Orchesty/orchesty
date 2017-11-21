<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\DataLayout;

use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\DataLayout\Exceptions\LayoutException;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutManager;
use CleverConnectors\AppBundle\Repository\DataLayoutRepository;
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
     *
     */
    public function testCreateDataLayout(): void
    {
        $systemInstall = new SystemInstall();
        $this->persistAndFlush($systemInstall);

        $this->manager->createDataLayout($systemInstall, [
            'action' => DataLayoutActionEnum::SUBSCRIBER,
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
            'action'        => DataLayoutActionEnum::SUBSCRIBER,
        ]);

        $this->assertInstanceOf(DataLayout::class, $dataLayout);
        $this->assertEquals([
            'action'         => 'subscriber',
            'system_install' => $systemInstall->getId(),
            'fields'         => [
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
            'action' => DataLayoutActionEnum::SUBSCRIBER,
            'fields' => [],
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
            'action'         => 'subscriber',
            'system_install' => $dataLayout->getSystemInstall(),
            'fields'         => [
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
        $this->persistAndFlush($systemInstall);

        $this->manager->createDataLayout($systemInstall, [
            'action' => DataLayoutActionEnum::SUBSCRIBER,
            'fields' => [
                ['key' => 'key-text', 'type' => TypeEnum::TEXT],
                ['key' => 'key-date', 'type' => TypeEnum::DATE],
                ['key' => 'key-bool', 'type' => TypeEnum::BOOL],
            ],
        ]);

        /** @var DataLayout $dataLayout */
        $dataLayout = $this->repository->findOneBy([
            'systemInstall' => $systemInstall->getId(),
            'action'        => DataLayoutActionEnum::SUBSCRIBER,
        ]);

        return $dataLayout;
    }

}