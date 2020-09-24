<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\BatchConnector;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class LoadApplicationUsersConnector
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\BatchConnector
 */
class LoadApplicationUsersConnector extends ConnectorAbstract implements BatchInterface
{

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;
    use BatchTrait;

    /**
     * @var DocumentManager
     */
    protected DocumentManager $dm;

    /**
     * @var string
     */
    protected string $appKey;

    /**
     * LoadApplicationUsersConnector constructor.
     *
     * @param DocumentManager $dm
     * @param string          $appKey
     */
    public function __construct(DocumentManager $dm, string $appKey)
    {
        $this->dm     = $dm;
        $this->appKey = $appKey;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return sprintf('load-application-users.%s', $this->appKey);
    }

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     * @throws ConnectorException
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $this->dm->clear();

        $filter = [ApplicationInstall::KEY => $this->appKey];
        $user   = $dto->getHeader(PipesHeaders::createKey(PipesHeaders::USER), NULL);
        $user   = is_array($user) ? reset($user) : $user;
        if ($user) {
            $filter[ApplicationInstall::USER] = $user;
        }

        /** @var ApplicationInstall[] $applicationInstalls */
        $applicationInstalls = array_values($this->dm->getRepository(ApplicationInstall::class)->findBy($filter));

        for ($i = 0; $i < count($applicationInstalls); $i++) {
            $applicationInstall = $applicationInstalls[$i];

            if ($this->getApplication()->isAuthorized($applicationInstall)) {
                $message = new SuccessMessage($i);
                $message
                    ->addHeader(PipesHeaders::createKey('id'), $applicationInstall->getId())
                    ->addHeader(PipesHeaders::createKey(PipesHeaders::APPLICATION), $applicationInstall->getKey())
                    ->addHeader(PipesHeaders::createKey(PipesHeaders::USER), $applicationInstall->getUser());
                $callbackItem($message);
            }
        }

        return $this->createPromise();
    }

}
