<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 14.9.17
 * Time: 13:35
 */

namespace Hanaboso\PipesFramework\User\Model\Messages;

use Hanaboso\PipesFramework\User\Model\MessageSubject;

/**
 * Class RegisterMessage
 *
 * @package Hanaboso\PipesFramework\User\Model\Messages
 */
class RegisterMessage extends UserMessageAbstract
{

    /**
     * @var string
     */
    protected $subject = MessageSubject::USER_REGISTER;

    /**
     * @var string|null
     */
    protected $template = NULL;

    /**
     * @return array
     */
    public function getMessage(): array
    {
        $this->message["to"] = $this->user->getEmail();

        return $this->message;
    }

}
