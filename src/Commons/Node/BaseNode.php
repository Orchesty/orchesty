<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20.3.2017
 * Time: 17:34
 */

namespace Hanaboso\PipesFramework\Commons\Node;

use Hanaboso\PipesFramework\Commons\BaseService\BaseServiceInterface;

/**
 * Class BaseNode
 *
 * @package Hanaboso\PipesFramework\Commons\Node
 */
abstract class BaseNode implements BaseServiceInterface, NodeInterface
{

    /**
     * @var string
     */
    private $id;

    /**
     * BaseNode constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}