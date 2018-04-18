<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\SocialMultichannel;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\EmbedSubscriber;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Model\SocialMultichannels\SocialMultichannelUpdateMirrorConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SocialMultichannelUpdateMirrorConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\SocialMultichannel
 */
final class SocialMultichannelUpdateMirrorConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers SocialMultichannelUpdateMirrorConnector::process()
     */
    public function testProcessExisting(): void
    {
        $mirr = $this->prepData();
        $node = new SocialMultichannelUpdateMirrorConnector($this->dm);

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            Comparator::KEY_PASS_DATA => [
                'audience' => [
                    'id' => 'audId',
                ],
                'client_id'   => 'cli',
            ],
            'create'                  => [
                'eml1',
                'eml2',
            ],
            'delete'                  => [
                'eml3',
            ],
        ]));

        $node->process($dto);
        $this->dm->clear();
        $mirr = $this->dm->find(AudienceMirror::class, $mirr->getId());

        self::assertEquals(['eml4', 'eml1', 'eml2'], $mirr->getSubscribers());
    }

    /**
     * @return AudienceMirror
     */
    private function prepData(): AudienceMirror
    {
        $mirr = new AudienceMirror();
        $mirr->addSubscriber(new EmbedSubscriber('eml3'))
            ->addSubscriber(new EmbedSubscriber('eml4'))
            ->setAudienceId('audId');
        $this->persistAndFlush($mirr);

        return $mirr;
    }

}