<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Topology;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\EmbedSubscriber;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class FacebookaudienceCreateAudienceTopologyTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Topology
 */
final class FacebookaudienceCreateAudienceTopologyTest extends DatabaseTestCaseAbstract
{

    /**
     * @var array
     */
    private $emls = [];

    /**
     * @covers FacebookaudienceGetCMListSubscribers::processBatch()
     *
     * @throws Exception
     */
    public function testTopology(): void
    {
        /** ************************************************************ **/
        /** REQUIRES SystemInstall, Topology and Nodes copied into mongo **/
        /** ************************************************************ **/
        $this->prepData();

        $data = [
            'name'               => 'testNamae',
            'page_id'            => '448171238945439',
            'campaign_objective' => 'LINK_CLICKS',
            'daily_budget'       => '5000',
            'bid_amount'         => '1',
            'client_id'          => 'cli',
            'billing_event'      => 'LINK_CLICKS',
            'status'             => 'PAUSED',
            'audience'           => [
                'id'   => 'someId',
                'name' => 'namae',
            ],
            'ad_data'            => [
                'image_content' => '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',
                'link'          => 'example.com',
                'description'   => 'desc',
                'title'         => 'titl',
            ],
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))
            ->setHeaders([
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'pf-guid'       => '5a8b121f-a74c-11e7-a177-000d3a20eb16',
                'pf-token'      => '+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI',
                'pf-system-key' => 'facebookaudience',
            ]);

        $dto = $this->facebookGetSubscribers($dto);
        $dto = $this->socialMultichannelGetMirror($dto);
        $dto = $this->comparator($dto);
        $dto = $this->facebookaudienceUpdateAudience($dto);
        $this->socialMultichannelUpdateMirror($dto);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    private function facebookGetSubscribers(ProcessDto $dto): ProcessDto
    {
        $data                           = json_decode($dto->getData(), TRUE);
        $res[Comparator::KEY_PASS_DATA] = $data;
        $res[Comparator::KEY_SOURCE]    = [$this->emls[0], $this->emls[1], $this->emls[3], $this->emls[4]];

        return $dto->setData(json_encode($res));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    private function socialMultichannelGetMirror(ProcessDto $dto): ProcessDto
    {
        $node = $this->container->get('hbpf.custom_node.socialmultichannel-get-mirror');
        $dto  = $node->process($dto);

        $data = json_decode($dto->getData(), TRUE);
        self::assertEquals([$this->emls[0], $this->emls[1], $this->emls[2]], $data[Comparator::KEY_DESTINATION]);

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    private function comparator(ProcessDto $dto): ProcessDto
    {
        $node = $this->container->get('hbpf.custom_node.comparator');
        $dto  = $node->process($dto);

        $data = json_decode($dto->getData(), TRUE);
        self::assertEquals([$this->emls[3], $this->emls[4]], $data['create']);
        self::assertEquals([$this->emls[2]], $data['delete']);

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws SystemException
     * @throws CurlException
     */
    private function facebookaudienceUpdateAudience(ProcessDto $dto): ProcessDto
    {
        $node = $this->container->get('hbpf.connector.facebookaudience-update-audience-connector');
        $dto  = $node->processAction($dto);

        $data = json_decode($dto->getData(), TRUE);
        self::assertEquals([$this->emls[3], $this->emls[4]], $data['create']);
        self::assertEquals([$this->emls[2]], $data['delete']);

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    private function socialMultichannelUpdateMirror(ProcessDto $dto): ProcessDto
    {
        $node = $this->container->get('hbpf.custom_node.socialmultichannel-update-mirror');
        $dto  = $node->process($dto);

        $data = json_decode($dto->getData(), TRUE);
        /** @var AudienceMirrorRepository $repo */
        $repo = $this->dm->getRepository(AudienceMirror::class);
        $mirr = $repo->getByAudience($data['audience']['id']);
        self::assertEquals([$this->emls[0], $this->emls[1], $this->emls[3], $this->emls[4]], $mirr->getSubscribers());

        return $dto;
    }

    /**
     *
     */
    private function prepData(): void
    {
        $this->emls = [
            $this->hash('eml1'),
            $this->hash('eml2'),
            $this->hash('eml3'),
            $this->hash('eml4'),
            $this->hash('eml5'),
        ];

        $mirr = new AudienceMirror();
        $mirr->setAudienceId('someId')
            ->setClientId('cli')
            ->addSubscriber(new EmbedSubscriber($this->emls[0]))
            ->addSubscriber(new EmbedSubscriber($this->emls[1]))
            ->addSubscriber(new EmbedSubscriber($this->emls[2]));

        $this->persistAndFlush($mirr);
    }

    /**
     * @param string $eml
     *
     * @return string
     */
    private function hash(string $eml): string
    {
        return hash('sha256', $eml);
    }

}