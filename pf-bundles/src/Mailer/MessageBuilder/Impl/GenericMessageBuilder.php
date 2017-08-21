<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:21 PM
 */

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl;

use Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl\GenericMessageBuilder\GenericTransportMessage;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderAbstract;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;

/**
 * Class GenericBuilderHandler
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder\Impl
 */
class GenericMessageBuilder extends MessageBuilderAbstract
{

    /**
     * @param array $data
     *
     * @return TransportMessageInterface
     * @throws MessageBuilderException
     */
    public function buildTransportMessage(array $data): TransportMessageInterface
    {
        if (!self::isValid($data)) {
            throw new MessageBuilderException('Invalid data.', MessageBuilderException::INVALID_DATA);
        }

        return new GenericTransportMessage(
            $data['from'],
            $data['to'],
            $data['subject'],
            $data['content'],
            $data['template'] ?? NULL
        );
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public static function isValid(array $data): bool
    {
        if (!isset($data['from']) || !filter_var($data['from'], FILTER_VALIDATE_EMAIL)) {
            return FALSE;
        }

        if (!isset($data['to']) || !filter_var($data['to'], FILTER_VALIDATE_EMAIL)) {
            return FALSE;
        }

        if (!isset($data['subject'])) {
            return FALSE;
        }

        if (!isset($data['content'])) {
            return FALSE;
        }

        return TRUE;
    }

}
