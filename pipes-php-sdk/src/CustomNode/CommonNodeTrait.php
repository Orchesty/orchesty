<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;

/**
 * Trait CommonNodeTrait
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
trait CommonNodeTrait
{

    /**
     * @var ApplicationInterface|null
     */
    protected ?ApplicationInterface $application = NULL;

    /**
     * @var DocumentManager|null
     */
    protected ?DocumentManager $db = NULL;

    /**
     * @return string
     */
    abstract function getName(): string;

    /**
     * @param ApplicationInterface $application
     *
     * @return self
     */
    public function setApplication(ApplicationInterface $application): self
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return ApplicationInterface
     * @throws CustomNodeException
     */
    public function getApplication(): ApplicationInterface
    {
        if ($this->application) {
            return $this->application;
        }

        throw new CustomNodeException('Application has not set.');
    }

    /**
     * @return string
     * @throws CustomNodeException
     */
    public function getApplicationKey(): string
    {
        if ($this->application) {
            return $this->application->getName();
        }

        throw new CustomNodeException('Application has not set.');
    }

    /**
     * @return DocumentManager
     * @throws CustomNodeException
     */
    public function getDb(): DocumentManager
    {
        if ($this->db) {
            return $this->db;
        }

        throw new CustomNodeException('MongoDbClient is not set.');
    }

    /**
     * @param DocumentManager|null $db
     *
     * @return self
     */
    public function setDb(?DocumentManager $db): self
    {
        $this->db = $db;

        return $this;
    }

    /**
     * @param string|null $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     */
    protected function getApplicationInstall(?string $user): ApplicationInstall {
        /** @var ApplicationInstallRepository $repo */
        $repo = $this->getDb()->getRepository(ApplicationInstall::class);
        if ($user) {
            return $repo->findUserApp($this->getApplicationKey(), $user);
        }

        return $repo->findOneByName($this->getApplicationKey());
    }

    /**
     * @param ProcessDtoAbstract $dto
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     */
    protected function getApplicationInstallFromProcess(ProcessDtoAbstract $dto): ApplicationInstall {
        $user = $dto->getUser();
        if (!$user) {
            throw new CustomNodeException('User not defined');
        }

        return $this->getApplicationInstall($user);
    }

}
