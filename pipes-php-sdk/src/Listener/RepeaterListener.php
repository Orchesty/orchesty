<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\PipesHeaders;
use Hanaboso\Utils\Traits\ControllerTrait;
use Hanaboso\Utils\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RepeaterListener
 *
 * @package Hanaboso\PipesPhpSdk\Listener
 */
class RepeaterListener implements EventSubscriberInterface, LoggerAwareInterface
{

    use ControllerTrait;
    use LoggerTrait;

    /**
     * @var NodeRepository
     */
    protected NodeRepository $nodeRepo;

    /**
     * RepeaterListener constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->logger = new NullLogger();

        /** @var NodeRepository $repo */
        $repo           = $dm->getRepository(Node::class);
        $this->nodeRepo = $repo;
    }

    /**
     * @param ExceptionEvent $event
     *
     * @throws Exception
     */
    public function onRepeatableException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        if (!$e instanceof OnRepeatException) {
            return;
        }

        $dto = $this->getRepeatedDto($e);

        $response = new Response($dto->getData(), 200, $dto->getHeaders());
        $event->setResponse($response);
        $event->allowCustomResponseCode();
    }

    /**
     * @return array<string, array<int|string, array<int|string, int|string>|int|string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onRepeatableException', 2_048],
        ];
    }

    /**
     * @param OnRepeatException $e
     *
     * @return ProcessDtoAbstract
     * @throws PipesFrameworkException
     */
    private function getRepeatedDto(OnRepeatException $e): ProcessDtoAbstract
    {
        $dto              = $e->getProcessDto();
        $node             = $this->nodeRepo->findOneBy(['id' => $dto->getHeader(PipesHeaders::NODE_ID, '')]);
        $repeaterSettings = $node?->getSystemConfigs();
        if ($repeaterSettings?->isRepeaterEnabled()){
            $dto->setRepeater(
                $repeaterSettings->getRepeaterInterval(),
                $repeaterSettings->getRepeaterHops(),
                $e->getMessage(),
            );
        } else {
            $dto->setRepeater($e->getInterval(),$e->getMaxHops(),$e->getMessage());
        }

        return $dto;
    }

}
