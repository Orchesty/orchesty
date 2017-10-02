<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Listeners;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DocumentListenerTest
 *
 * @package Tests\Integration\AppBundle\Listeners
 */
class DocumentListenerTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dm->getConnection()->dropDatabase('clever-connectors');
    }

    /**
     *
     */
    public function testSubscribe(): void
    {
        $array         = ['key' => 'value'];
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings($array);

        $this->dm->persist($systemInstall);
        $this->dm->flush($systemInstall);
        $this->dm->clear();

        /** @var SystemInstall $dbRecord */
        $dbRecord = $this->dm->getRepository(SystemInstall::class)->findOneBy(['id' => $systemInstall->getId()]);

        self::assertSame($array, $dbRecord->getSettings());
    }

}