<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Document;

use Exception;
use Hanaboso\HbPFAppStore\Document\Synchronization;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;

/**
 * Class SynchronizationTest
 *
 * @package HbPFAppStoreTests\Integration\Document
 */
final class SynchronizationTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getUser
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setUser
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getStatus
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setStatus
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getInternalId
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setInternalId
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setExternalId
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getExternalId
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getInternalHash
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setInternalHash
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getExternalHash
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setExternalHash
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::setData
     * @covers \Hanaboso\HbPFAppStore\Document\Synchronization::getData
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $synchronization = (new Synchronization())
            ->setUser('user')
            ->setStatus('status')
            ->setInternalId('1')
            ->setExternalId('2')
            ->setInternalHash('hash')
            ->setExternalHash('hash')
            ->setData(['data']);

        $this->persistAndFlush($synchronization);

        self::assertEquals('user', $synchronization->getUser());
        self::assertEquals('status', $synchronization->getStatus());
        self::assertEquals('1', $synchronization->getInternalId());
        self::assertEquals('hash', $synchronization->getInternalHash());
        self::assertEquals('2', $synchronization->getExternalId());
        self::assertEquals('hash', $synchronization->getExternalHash());
        self::assertEquals(['data'], $synchronization->getData());
    }

}
