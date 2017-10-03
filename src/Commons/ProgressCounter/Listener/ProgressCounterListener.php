<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 18:18
 */

namespace Hanaboso\PipesFramework\Commons\ProgressCounter\Listener;

use Hanaboso\PipesFramework\Commons\ProgressCounter\Event\ProgressCounterEvent;
use Hanaboso\PipesFramework\Commons\ProgressCounter\ProgressCounterAbstract;
use Hanaboso\PipesFramework\Commons\ProgressCounter\ProgressCounterTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProgressCounterListener
 */
class ProgressCounterListener implements EventSubscriberInterface
{

    use ProgressCounterTrait;

    /**
     * @var ProgressCounterAbstract
     */
    protected $counter;

    /**
     * ProgressCounterListener constructor.
     *
     * @param ProgressCounterAbstract $counter
     *
     */
    public function __construct(ProgressCounterAbstract $counter)
    {
        $this->counter = $counter;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProgressCounterEvent::ON_PROGRESS_SET_STATUS => 'onProgressSetStatus',
        ];
    }

    /**
     * @param ProgressCounterEvent $event
     */
    public function onProgressSetStatus(ProgressCounterEvent $event): void
    {
        $this->counter->setStatus($event->getProcessId(), $event->getStatus());
    }

}
