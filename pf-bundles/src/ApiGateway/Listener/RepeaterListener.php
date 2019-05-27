<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\ApiGateway\Exceptions\OnRepeatException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfigDto;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RepeaterListener
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Listener
 */
class RepeaterListener implements EventSubscriberInterface, LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * RepeaterListener constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->logger = new NullLogger();
        $this->dm     = $dm;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onRepeatableException',
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws Exception
     */
    public function onRepeatableException(GetResponseForExceptionEvent $event): void
    {
        $e = $event->getException();

        if (!$e instanceof OnRepeatException) {
            return;
        }

        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $repeatCode     = PipesHeaders::createKey(PipesHeaders::RESULT_CODE);
        $dto            = $e->getProcessDto();

        $nodeRepository = $this->dm->getRepository(Node::class);
        /** @var Node|null $node */
        $node = $nodeRepository->find($dto->getHeaders()[PipesHeaders::createKey(PipesHeaders::NODE_ID)] ?? '');

        if (!$dto->getHeader($repeatHops) && !$dto->getHeader($repeatMaxHops) && !$dto->getHeader($repeatInterval)) {
            if ($node) {
                /** @var SystemConfigDto $configs */
                $configs = $node->getSystemConfigs();

                if (!$configs->isRepeaterEnabled()) {
                    throw new Exception('Repeater is not enabled.');
                }
                $dto
                    ->addHeader($repeatInterval, (string) $configs->getRepeaterInterval())
                    ->addHeader($repeatMaxHops, (string) $configs->getRepeaterHops());
            } else {
                $dto
                    ->addHeader($repeatInterval, (string) $e->getInterval())
                    ->addHeader($repeatMaxHops, (string) $e->getMaxHops());
            }
            $dto
                ->addHeader($repeatCode, '1001')
                ->addHeader($repeatHops, '0');
        }

        $currentHop = $dto->getHeader($repeatHops);
        $maxHop     = $dto->getHeader($repeatMaxHops);
        $currentHop = is_array($currentHop) ? $currentHop[0] : $currentHop;
        $maxHop     = is_array($maxHop) ? $maxHop[0] : $maxHop;

        if ($currentHop < $maxHop) {
            $currentHop++;
            $e->getProcessDto()->addHeader($repeatHops, (string) $currentHop);
        }

        $this->logger->info(
            'Repeater info.',
            ['currentHop' => $currentHop, 'interval' => $e->getInterval(), 'maxHops' => $maxHop]
        );

        $response = new Response($e->getProcessDto()->getData(), 200, $e->getProcessDto()->getHeaders());
        $event->setResponse($response);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
