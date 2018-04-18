<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Document;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\EmbedSubscriber;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AudienceMirrorTest
 *
 * @package Tests\Integration\AppBundle\Document
 */
final class AudienceMirrorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var string
     */
    private $id;

    /**
     *
     */
    public function testEmbedSubscribers(): void
    {
        $mirr = new AudienceMirror();
        $mirr->addSubscriber(new EmbedSubscriber('eml1'))
            ->addSubscriber(new EmbedSubscriber('eml2'));
        $this->persistAndFlush($mirr);
        $this->id = $mirr->getId();

        $mirr->addSubscriber(new EmbedSubscriber('eml3'));
        $mirr = $this->update();
        self::assertEquals(3, count($mirr->getSubscribers()));

        $mirr->removeSubscribeByIndex(1);
        $mirr = $this->update();
        self::assertEquals(2, count($mirr->getSubscribers()));
        self::assertEquals(['eml1', 'eml3'], $mirr->getSubscribers());

        $mirr->removeSubscriberByEmail('eml1');
        $mirr = $this->update();
        self::assertEquals(1, count($mirr->getSubscribers()));
        self::assertEquals(['eml3'], $mirr->getSubscribers());
    }

    /**
     * @return AudienceMirror
     */
    private function update(): AudienceMirror
    {
        $this->dm->flush();
        $this->dm->clear();

        return $this->dm->find(AudienceMirror::class, $this->id);
    }

}