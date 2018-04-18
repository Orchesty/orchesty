<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SocialMultichannels;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\EmbedSubscriber;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class SocialMultichannelUpdateMirrorConnector
 *
 * @package CleverConnectors\AppBundle\Model\SocialMultichannels
 */
class SocialMultichannelUpdateMirrorConnector implements CustomNodeInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * GetAudienceMirrorConnector constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        /** @var AudienceMirrorRepository $repo */
        $repo = $this->dm->getRepository(AudienceMirror::class);
        $mirr = $repo->getByAudience($data[Comparator::KEY_PASS_DATA]['audience']['id']);

        foreach ($data['create'] as $eml) {
            $mirr->addSubscriber(new EmbedSubscriber($eml));
        }
        foreach ($data['delete'] as $eml) {
            $mirr->removeSubscriberByEmail($eml);
        }
        $this->dm->flush();

        return $dto->setData(json_encode($data[Comparator::KEY_PASS_DATA]));
    }

}