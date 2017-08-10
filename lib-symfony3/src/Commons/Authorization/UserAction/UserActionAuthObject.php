<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 17:23
 */

namespace Hanaboso\PipesFramework\Commons\Authorization\UserAction;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class UserActionAuthObject
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\UserAction
 */
class UserActionAuthObject
{

    /**
     * @Serializer\Type("string")
     *
     * @var string
     */
    private $type;

    /**
     * UserActionAuthObject constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

}