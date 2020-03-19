<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\Utils\Traits\ControllerTrait;
use RabbitMqBundle\Connection\ConnectionManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class StatusController
 *
 * @package Hanaboso\NotificationSender\Controller
 *
 * @Route("/status")
 */
final class StatusController
{

    use ControllerTrait;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var ConnectionManager
     */
    private ConnectionManager $manager;

    /**
     * StatusController constructor.
     *
     * @param DocumentManager   $dm
     * @param ConnectionManager $manager
     */
    public function __construct(DocumentManager $dm, ConnectionManager $manager)
    {
        $this->dm      = $dm;
        $this->manager = $manager;
    }

    /**
     * @Route("", methods={"GET", "OPTIONS"})
     * @Route("/", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getStatusAction(): Response
    {
        try {
            $this->dm->getClient()->listDatabases();
            $database = TRUE;
        } catch (Throwable $t) {
            $database = FALSE;
        }

        try {
            $rabbitMq = $this->manager->getConnection()->getClient()->isConnected();
        } catch (Throwable $t) {
            $rabbitMq = FALSE;
        }

        return $this->getResponse(['database' => $database, 'rabbitmq' => $rabbitMq]);
    }

}
