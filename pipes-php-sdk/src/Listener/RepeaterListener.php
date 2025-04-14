<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Listener;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Node;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository\NodeFilter;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository\NodeRepository;
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
     * RepeaterListener constructor.
     *
     * @param NodeRepository $nodeRepository
     */
    public function __construct(private readonly NodeRepository $nodeRepository)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param ExceptionEvent $event
     *
     * @return void
     * @throws GuzzleException
     * @throws PipesFrameworkException
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
     * @return array<string, list<array{0: string, 1?: int}|int|string>|string>
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
     * @throws GuzzleException
     */
    private function getRepeatedDto(OnRepeatException $e): ProcessDtoAbstract
    {
        $dto = $e->getProcessDto();
        /** @var Node $node */
        $node             = $this->nodeRepository->findOne(
            new NodeFilter(ids: [$dto->getHeader(PipesHeaders::NODE_ID, '')]),
        );
        $repeaterSettings = $node->getSystemConfigs();
        if ($repeaterSettings?->isRepeaterEnabled()) {
            $dto->setRepeater(
                $repeaterSettings->getRepeaterInterval(),
                $repeaterSettings->getRepeaterHops(),
                $e->getMessage(),
            );
        } else {
            $dto->setRepeater($e->getInterval(), $e->getMaxHops(), $e->getMessage());
        }

        return $dto;
    }

}
