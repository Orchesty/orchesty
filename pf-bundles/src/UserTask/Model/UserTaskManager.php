<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskException;
use Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository;
use Hanaboso\Utils\System\PipesHeaders;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class UserTaskManager
 *
 * @package Hanaboso\PipesFramework\UserTask\Model
 */
final class UserTaskManager
{

    private const STATE_HEADER = 'user-task-state';
    private const STATE_ACCEPT = 'accept';
    private const STATE_REJECT = 'reject';

    /**
     * @var UserTaskRepository
     */
    private UserTaskRepository $repo;

    /**
     * UserTaskManager constructor.
     *
     * @param DocumentManager $dm
     * @param UserTaskFilter  $filter
     * @param Publisher       $publisher
     */
    public function __construct(
        private DocumentManager $dm,
        private UserTaskFilter $filter,
        private Publisher $publisher,
    )
    {
        /** @var UserTaskRepository $repo */
        $repo       = $dm->getRepository(UserTask::class);
        $this->repo = $repo;
    }

    /**
     * @param string $id
     *
     * @return UserTask
     * @throws MongoDBException
     * @throws MappingException
     */
    public function get(string $id): UserTask
    {
        /** @var UserTask|null $doc */
        $doc = $this->repo->find($id);
        if (!$doc) {
            throw new UserTaskException(
                sprintf('UserTask with id [%s] has been not found', $id),
            );
        }

        return $doc;
    }

    /**
     * @param UserTask $userTask
     * @param mixed[]  $data
     *
     * @return UserTask
     * @throws MongoDBException
     */
    public function update(UserTask $userTask, array $data): UserTask
    {
        $message = $userTask->getMessage();
        $userTask->addAuditLog($message->toArray());
        $message->fromArray($data);
        $this->dm->flush();

        return $userTask;
    }

    /**
     * @param UserTask $userTask
     *
     * @throws MongoDBException
     */
    public function accept(UserTask $userTask): void
    {
        $this->publish($userTask, TRUE);
        $this->delete($userTask);
    }

    /**
     * @param UserTask $userTask
     *
     * @throws MongoDBException
     */
    public function reject(UserTask $userTask): void
    {
        $this->publish($userTask, FALSE);
        $this->delete($userTask);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function filter(GridRequestDtoInterface $dto): array
    {
        return $this->filter->getData($dto)->toArray();
    }

    /**
     * @param UserTask $userTask
     * @param bool     $accept
     */
    private function publish(UserTask $userTask, bool $accept): void
    {
        $message = $userTask->getMessage();
        $this->publisher->setExchange($userTask->getReturnExchange());
        $this->publisher->setRoutingKey($userTask->getReturnRoutingKey());

        $headers = $message->getHeaders();

        $headers[PipesHeaders::createKey(self::STATE_HEADER)] = $accept ? self::STATE_ACCEPT : self::STATE_REJECT;
        $this->publisher->publish($message->getBody(), $headers);
    }

    /**
     * @param UserTask $userTask
     *
     * @throws MongoDBException
     */
    private function delete(UserTask $userTask): void
    {
        $this->dm->remove($userTask);
        $this->dm->flush();
    }

}
