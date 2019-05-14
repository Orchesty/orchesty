<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Consumer;

use Bunny\Message;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeManager;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use Throwable;

/**
 * Class LongRunningNodeCallback
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Consumer
 */
class LongRunningNodeCallback extends SyncCallbackAbstract
{

    /**
     * @var LongRunningNodeManager
     */
    private $manager;

    /**
     * @var LongRunningNodeLoader
     */
    private $loader;

    /**
     * LongRunningNodeCallback constructor.
     *
     * @param LongRunningNodeManager $manager
     * @param LongRunningNodeLoader  $loader
     */
    public function __construct(
        LongRunningNodeManager $manager,
        LongRunningNodeLoader $loader
    )
    {
        parent::__construct();
        $this->manager = $manager;
        $this->loader  = $loader;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    public function handle($data, Message $message): CallbackStatus
    {
        $data;
        try {
            $serv = $this->loader->getLongRunningNode($message->getHeader(PipesHeaders::createKey(PipesHeaders::NODE_NAME)));

            $doc = $serv->beforeAction($message);
            $this->manager->saveDocument($doc);

            return new CallbackStatus(CallbackStatus::SUCCESS);
        } catch (Throwable $e) {
            return new CallbackStatus(CallbackStatus::RESEND);
        }
    }

}
