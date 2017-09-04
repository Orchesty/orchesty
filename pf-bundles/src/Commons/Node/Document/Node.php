<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Node\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;

/**
 * Class Node
 *
 * @MongoDB\Document(repositoryClass="Hanaboso\PipesFramework\Commons\Node\NodeRepository")
 *
 * @package Hanaboso\PipesFramework\Commons\Node\Document
 */
class Node
{

    use IdTrait;

}