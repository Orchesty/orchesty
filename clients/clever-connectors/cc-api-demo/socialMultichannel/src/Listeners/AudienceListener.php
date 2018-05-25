<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Listeners;

use CcApi\Curl\Exception\CurlException;
use CleverCore\SocialMultichannel\Entities\Audience;
use CleverCore\SocialMultichannel\Models\AudienceFacade;
use CleverCore\SocialMultichannel\Repositories\AudienceRepository;
use Doctrine\ORM\EntityManager;
use Kdyby\Events\Subscriber;

/**
 * Class AudienceListener
 *
 * @package CleverCore\SocialMultichannel\Listeners
 */
final class AudienceListener implements Subscriber
{

    public const LIST_ON_CREATE = 'List::onCreate';
    public const LIST_ON_UPDATE = 'List::onUpdate';
    public const LIST_ON_DELETE = 'List::onDelete';

    public const SEGMENT_ON_CREATE = 'Segment::onCreate';
    public const SEGMENT_ON_UPDATE = 'Segment::onUpdate';
    public const SEGMENT_ON_DELETE = 'Segment::onDelete';

    /**
     * @var AudienceRepository
     */
    private $repository;
    /**
     * @var AudienceFacade
     */
    private $facade;

    /**
     * AudienceListener constructor.
     *
     * @param EntityManager  $em
     * @param AudienceFacade $facade
     */
    public function __construct(EntityManager $em, AudienceFacade $facade)
    {
        /** @var AudienceRepository $repository */
        $repository       = $em->getRepository(Audience::class);
        $this->repository = $repository;
        $this->facade     = $facade;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            self::LIST_ON_CREATE    => 'onList',
            self::LIST_ON_UPDATE    => 'onList',
            self::LIST_ON_UPDATE    => 'onList',
            self::SEGMENT_ON_CREATE => 'onSegment',
            self::SEGMENT_ON_UPDATE => 'onSegment',
            self::SEGMENT_ON_DELETE => 'onSegment',
        ];
    }

    /**
     * @param string $id
     *
     * @throws CurlException
     */
    public function onList(string $id): void
    {
        $audiences = $this->repository->getByList($id);

        if ($audiences) {
            $this->facade->runBatchUpdate($audiences);
        }
    }

    /**
     * @param string $id
     *
     * @throws CurlException
     */
    public function onSegment(string $id): void
    {
        $audiences = $this->repository->getBySegment($id);

        if ($audiences) {
            $this->facade->runBatchUpdate($audiences);
        }
    }

}