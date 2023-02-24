<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use GuzzleHttp\Exception\GuzzleException;
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
     * @return string
     */
    abstract function getName(): string;

    /**
     * CommonNodeTrait contructor.
     *
     * @param ApplicationInstallRepository $applicationInstallRepository
     */
    public function __construct(private readonly ApplicationInstallRepository $applicationInstallRepository)
    {
    }

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
     * @param string|null $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws GuzzleException
     */
    protected function getApplicationInstall(?string $user): ApplicationInstall {
        if ($user) {
            return $this->applicationInstallRepository->findUserApp($this->getApplicationKey(), $user);
        }

        return $this->applicationInstallRepository->findOneByName($this->getApplicationKey());
    }

    /**
     * @param ProcessDtoAbstract $dto
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws GuzzleException
     */
    protected function getApplicationInstallFromProcess(ProcessDtoAbstract $dto): ApplicationInstall {
        $user = $dto->getUser();
        if (!$user) {
            throw new CustomNodeException('User not defined');
        }

        return $this->getApplicationInstall($user);
    }

}
