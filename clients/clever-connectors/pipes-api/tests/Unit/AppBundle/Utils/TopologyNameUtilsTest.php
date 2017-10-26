<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Utils;

use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use LogicException;
use Tests\KernelTestCaseAbstract;

/**
 * Class TopologyNameUtilsTest
 *
 * @package Tests\Unit\AppBundle\Utils
 */
final class TopologyNameUtilsTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetServiceTopologyName(): void
    {
        self::assertEquals(
            'refresh-token',
            TopologyNameUtils::getServiceTopologyName(TopologyNameUtils::REFRESH_TOKEN)
        );
        self::assertEquals(
            'sys-refresh-token',
            TopologyNameUtils::getServiceTopologyName(TopologyNameUtils::REFRESH_TOKEN, 'sys')
        );

        $this->expectException(LogicException::class);
        TopologyNameUtils::getServiceTopologyName(TopologyNameUtils::CREATE_PERSON, 'sys');
    }

    /**
     *
     */
    public function testGetTopologyName(): void
    {
        self::assertEquals(
            'sys-sync-subscribers',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, 'sys')
        );
        self::assertEquals(
            'usr-sys-sync-subscribers',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, 'sys', 'usr')
        );

        $this->expectException(LogicException::class);
        TopologyNameUtils::getTopologyName(TopologyNameUtils::REFRESH_TOKEN, 'sys');
    }

}