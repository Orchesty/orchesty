<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;

/**
 * Class UserTaskRepository
 *
 * @package         Hanaboso\PipesFramework\UserTask\Repository
 *
 * @phpstan-extends DocumentRepository<UserTask>
 */
final class UserTaskRepository extends DocumentRepository
{

}
