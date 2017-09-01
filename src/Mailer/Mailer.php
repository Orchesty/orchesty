<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 4/3/17
 * Time: 12:00 PM
 */

namespace Hanaboso\PipesFramework\Mailer;

use Hanaboso\PipesFramework\Mailer\Exception\MailerException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportInterface;
use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

/**
 * Class Mailer
 *
 * @package Hanaboso\PipesFramework\Mailer
 */
class Mailer
{

    /**
     * @var TransportInterface
     */
    private $transport;
    /**
     * @var null|EngineInterface
     */
    private $templateEngine;

    /**
     * Mailer constructor.
     *
     * @param TransportInterface   $transport
     * @param EngineInterface|null $templateEngine
     */
    public function __construct(TransportInterface $transport, ?EngineInterface $templateEngine = NULL)
    {
        $this->transport      = $transport;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param TransportMessageInterface $message
     *
     * @throws MailerException
     */
    public function renderAndSend(TransportMessageInterface $message): void
    {
        if ($message->getTemplate()) {
            if (!$this->templateEngine) {
                throw new MailerException(
                    'Missing template engine. Can not render message.',
                    MailerException::MISSING_TEMPLATE_ENGINE
                );
            }
            $message->setContent($this->templateEngine->render($message->getTemplate(), $message->getDataContent()));
        }
        $this->transport->send($message);
    }

    /**
     * @param TransportMessageInterface $message
     *
     * @return bool
     * @throws MailerException
     */
    public function renderAndSendTest(TransportMessageInterface $message): bool
    {
        if ($message->getTemplate()) {
            if (!$this->templateEngine) {
                throw new MailerException(
                    'Missing template engine. Can not render message.',
                    MailerException::MISSING_TEMPLATE_ENGINE
                );
            }
        }

        return TRUE;
    }

}
