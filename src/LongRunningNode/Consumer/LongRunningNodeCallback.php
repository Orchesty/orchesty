<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Consumer;

use Bunny\Message;
use Doctrine\ODM\MongoDB\DocumentManager;
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
     * @var DocumentManager
     */
    private $dm;

    /**
     * LongRunningNodeCallback constructor.
     *
     * @param LongRunningNodeManager $manager
     * @param LongRunningNodeLoader  $loader
     * @param DocumentManager        $dm
     */
    public function __construct(
        LongRunningNodeManager $manager,
        LongRunningNodeLoader $loader,
        DocumentManager $dm
    )
    {
        parent::__construct();
        $this->manager = $manager;
        $this->loader  = $loader;
        $this->dm      = $dm;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    function handle($data, Message $message): CallbackStatus
    {
        $data;
        try {
            $doc  = $this->manager->getDocument(
                $message->getHeader(PipesHeaders::TOPOLOGY_ID),
                $message->getHeader(PipesHeaders::NODE_ID),
                $message->getHeader(PipesHeaders::PROCESS_ID, NULL)
            );
            $serv = $this->loader->getLongRunningNode($message->getHeader(PipesHeaders::NODE_NAME));
            $serv->beforeAction($doc, $doc->toProcessDto());
            $this->dm->flush();

            return new CallbackStatus(CallbackStatus::SUCCESS);
        } catch (Throwable $e) {
            return new CallbackStatus(CallbackStatus::RESEND);
        }
    }

}