<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Manager;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\User\Filter\UserDocumentFilter;

/**
 * Class UserManager
 *
 * @package Hanaboso\PipesFramework\User\Manager
 */
final class UserManager
{

    use GridHandlerTrait;

    /**
     * UserManager constructor.
     *
     * @param UserDocumentFilter $userFilter
     */
    public function __construct(private UserDocumentFilter $userFilter)
    {
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws Exception
     */
    public function getArrayOfUsers(GridRequestDto $dto): array
    {
        return $this->getGridResponse($dto, $this->userFilter->getData($dto)->toArray());
    }

}
