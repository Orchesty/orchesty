<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/10/17
 * Time: 10:49 AM
 */

namespace Hanaboso\PipesFramework\Commons\Node;

use Hanaboso\PipesFramework\Commons\Message\MessageInterface;

/**
 * Interface NodeInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Node
 */
interface NodeInterface
{

    /**
     * @param string           $id
     * @param MessageInterface $message
     *
     * @return mixed
     */
    public function processData(string $id, MessageInterface $message);

}
