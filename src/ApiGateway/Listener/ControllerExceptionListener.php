<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Listener;

use Hanaboso\CommonsBundle\Crypt\Exceptions\CryptException;
use Hanaboso\CommonsBundle\Exception\FileStorageException;
use Hanaboso\CommonsBundle\Transport\Ftp\Exception\FtpException;
use Hanaboso\CommonsBundle\Transport\Soap\SoapException;
use Hanaboso\PipesFramework\Notification\Exception\NotificationException;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\Utils\System\PipesHeaders;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ControllerExceptionListener
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Listener
 */
class ControllerExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var mixed[]
     */
    protected array $exceptionClasses = [
        ApplicationInstallException::class,
        AuthorizationException::class,
        ConnectorException::class,
        CryptException::class,
        CustomNodeException::class,
        EnumException::class,
        FileStorageException::class,
        FtpException::class,
        JoinerException::class,
        LongRunningNodeException::class,
        MapperException::class,
        NotificationException::class,
        PipesFrameworkException::class,
        SoapException::class,
        TableParserException::class,
        TableParserHandlerException::class,
    ];

    /**
     * ControllerExceptionListener constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param ExceptionEvent $event
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        if (!$e instanceof PipesFrameworkExceptionAbstract) {
            return;
        }

        $this->logger->error('Controller exception.', ['exception' => $e]);

        if (!in_array(get_class($e), $this->exceptionClasses, TRUE)) {
            return;
        }

        $response = $this->getErrorResponse($e, 200);
        $response->headers->add(PipesHeaders::clear($event->getRequest()->headers->all()));

        if (in_array(get_class($e), [ConnectorException::class], TRUE)) {
            /** @var ConnectorException $exception */
            $exception = $e;
            $dto       = $exception->getProcessDto();

            if ($dto) {
                $response->headers->add(PipesHeaders::clear($dto->getHeaders()));
            }
        }

        $response->headers->set(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), '1006');

        $event->setResponse($response);
        $event->allowCustomResponseCode();
    }

    /**
     * @param mixed[] $exceptionClasses
     *
     * @return ControllerExceptionListener
     */
    public function setExceptionClasses(array $exceptionClasses): ControllerExceptionListener
    {
        $this->exceptionClasses = $exceptionClasses;

        return $this;
    }

    /**
     * @return array<string, array<int|string, array<int|string, int|string>|int|string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

}
