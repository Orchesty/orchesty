<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SocialMultichannels;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class SocialMultichannelGetMirrorConnector
 *
 * @package CleverConnectors\AppBundle\Model\SocialMultichannels
 */
class SocialMultichannelGetMirrorConnector implements CustomNodeInterface
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
     * @throws EnumException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data  = json_decode($dto->getData(), TRUE);
        $props = $data[Comparator::KEY_PASS_DATA];
        /** @var AudienceMirrorRepository $repo */
        $repo = $this->dm->getRepository(AudienceMirror::class);

        $mirr = $repo->getByAudience($props['audience']['id'], $props['type']);
        if (!$mirr) {
            $mirr = new AudienceMirror();
            $mirr->setAudienceId($props['audience']['id'])
                ->setClientId($props['client_id'])
                ->setType($props['type']);
            $this->dm->persist($mirr);
            $this->dm->flush();
        }

        $data[Comparator::KEY_DESTINATION]              = $mirr->getSubscribers();
        $data[Comparator::KEY_PASS_DATA]['audience_id'] = $mirr->getSystemAudienceId();

        return $dto->setData(json_encode($data));
    }

}