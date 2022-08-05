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
     * StatusController constructor.
     *
     * @param DocumentManager   $dm
     * @param ConnectionManager $manager
     */
    public function __construct(private DocumentManager $dm, private ConnectionManager $manager)
    {
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
            $t;
            $database = FALSE;
        }

        try {
            $rabbitMq = $this->manager->getConnection()->getClient()->isConnected();
        } catch (Throwable $t) {
            $t;
            $rabbitMq = FALSE;
        }

        return $this->getResponse(['database' => $database, 'rabbitmq' => $rabbitMq]);
    }

}
