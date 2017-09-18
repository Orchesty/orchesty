<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 14.9.17
 * Time: 13:07
 */

namespace Hanaboso\PipesFramework\User\Model\Messages;

use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class UserMessageAbstract
 *
 * @package Hanaboso\PipesFramework\User\Model\Messages
 */
abstract class UserMessageAbstract
{

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $message = [
        "to"          => "",
        "subject"     => "",
        "content"     => "",
        "dataContent" => [],
        "template"    => "",
    ];

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * UserMessageAbstract constructor.
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user)
    {
        $this->user                = $user;
        $this->message["subject"]  = $this->subject;
        $this->message["template"] = $this->template;
    }

    /**
     * @return array
     */
    abstract public function getMessage(): array;

}
