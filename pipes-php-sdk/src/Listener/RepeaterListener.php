<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\Utils\System\PipesHeaders;
use Hanaboso\Utils\Traits\ControllerTrait;
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
final class RepeaterListener implements EventSubscriberInterface, LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var ObjectRepository<Node>|NodeRepository
     */
    private $repo;

    /**
     * RepeaterListener constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->logger = new NullLogger();
        $this->repo   = $dm->getRepository(Node::class);
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

        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode     = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);
        $dto            = $e->getProcessDto();

        if ((!$dto->getHeader($repeatHops) && $dto->getHeader($repeatHops) <= 0) &&
            !$dto->getHeader($repeatMaxHops) && !$dto->getHeader($repeatInterval)
        ) {

            [$interval, $hops, $enabled] = $this->getRepeaterStuff($e, $dto);

            if (!$enabled) {
                return;
            }

            $dto
                ->addHeader($repeatInterval, (string) $interval)
                ->addHeader($repeatMaxHops, (string) $hops)
                ->addHeader($repeatCode, '1001')
                ->addHeader($repeatHops, '0');
        }

        $currentHop = $dto->getHeader($repeatHops);
        $maxHop     = $dto->getHeader($repeatMaxHops);
        $currentHop = is_array($currentHop) ? $currentHop[0] : $currentHop;
        $maxHop     = is_array($maxHop) ? $maxHop[0] : $maxHop;

        if ($currentHop < $maxHop) {
            $dto
                ->addHeader($repeatCode, '1001')
                ->addHeader($repeatHops, (string) ++$currentHop);
        } else {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);
        }

        $this->logger->info(
            'Repeater info.',
            ['currentHop' => $currentHop, 'interval' => $e->getInterval(), 'maxHops' => $maxHop]
        );

        $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE), $e->getMessage());

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
            KernelEvents::EXCEPTION => 'onRepeatableException',
        ];
    }

    /**
     * @param OnRepeatException $e
     * @param ProcessDto        $dto
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     */
    private function getRepeaterStuff(OnRepeatException $e, ProcessDto $dto): array
    {
        /** @var Node|null $node */
        $node = $this->repo->find(PipesHeaders::get(PipesHeaders::NODE_ID, $dto->getHeaders()) ?: '');
        if ($node) {
            $configs = $node->getSystemConfigs();
            if ($configs) {
                return [$configs->getRepeaterInterval(), $configs->getRepeaterHops(), $configs->isRepeaterEnabled()];
            }
        }

        return [$e->getInterval(), $e->getMaxHops(), TRUE];
    }

}
