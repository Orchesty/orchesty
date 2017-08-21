<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 21.8.17
 * Time: 14:53
 */

namespace Hanaboso\PipesFramework\HbPFMailerBundle\Handler;

use Hanaboso\PipesFramework\HbPFMailerBundle\Loader\MailBuildersLoader;
use Hanaboso\PipesFramework\Mailer\Mailer;

/**
 * Class MailHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\Handler
 */
class MailHandler
{

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var MailBuildersLoader
     */
    private $buildersLoader;

    /**
     * MailHandler constructor.
     *
     * @param Mailer             $mailer
     * @param MailBuildersLoader $buildersLoader
     */
    public function __construct(Mailer $mailer, MailBuildersLoader $buildersLoader)
    {
        $this->mailer         = $mailer;
        $this->buildersLoader = $buildersLoader;
    }

    /**
     * @param string $builderId
     * @param array  $data
     */
    public function send(string $builderId, array $data): void
    {
        $builder = $this->buildersLoader->getBuilder($builderId);

        $this->mailer->renderAndSend($builder->buildTransportMessage($data));
    }

    /**
     * @param string $builderId
     * @param array  $data
     */
    public function testSend(string $builderId, array $data): void
    {
        $builder = $this->buildersLoader->getBuilder($builderId);

        $this->mailer->renderAndSendTest($builder->buildTransportMessage($data));
    }

}
