<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\SocialMultichannel;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\EmbedSubscriber;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Model\SocialMultichannels\SocialMultichannelGetMirrorConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SocialMultichannelGetMirrorConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\SocialMultichannel
 */
final class SocialMultichannelGetMirrorConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers SocialMultichannelGetMirrorConnector::process()
     */
    public function testProcessExisting(): void
    {
        $this->prepData();
        $node = new SocialMultichannelGetMirrorConnector($this->dm);

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            Comparator::KEY_PASS_DATA => [
                'audience'  => [
                    'id' => 'audId',
                ],
                'client_id' => 'cli',
            ],
        ]));

        $res  = $node->process($dto);
        $body = json_decode($res->getData(), TRUE);
        self::assertEquals(['eml1', 'eml2'], $body[Comparator::KEY_DESTINATION]);
        self::assertArrayHasKey('audience_id', $body[Comparator::KEY_PASS_DATA]);
    }

    /**
     * @covers SocialMultichannelGetMirrorConnector::process()
     */
    public function testProcessNew(): void
    {
        $node = new SocialMultichannelGetMirrorConnector($this->dm);

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            Comparator::KEY_PASS_DATA => [
                'audience'  => [
                    'id' => 'audId',
                ],
                'client_id' => 'cli',
            ],
        ]));

        $res  = $node->process($dto);
        $body = json_decode($res->getData(), TRUE);
        self::assertEquals([], $body[Comparator::KEY_DESTINATION]);
        self::assertArrayHasKey('audience_id', $body[Comparator::KEY_PASS_DATA]);
    }

    /**
     * @return AudienceMirror
     */
    private function prepData(): AudienceMirror
    {
        $mirr = new AudienceMirror();
        $mirr->addSubscriber(new EmbedSubscriber('eml1'))
            ->addSubscriber(new EmbedSubscriber('eml2'))
            ->setAudienceId('audId');
        $this->persistAndFlush($mirr);

        return $mirr;
    }

}