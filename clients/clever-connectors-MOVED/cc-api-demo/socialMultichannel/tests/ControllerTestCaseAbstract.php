<?php declare(strict_types=1);

namespace Tests;

use LogicException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Http\IRequest;
use Nette\Utils\Json;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
abstract class ControllerTestCaseAbstract extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @param string $presenterName
     * @param string $actionName
     * @param string $method
     * @param array  $parameters
     * @param array  $body
     *
     * @return array
     * @throws LogicException
     */
    public function sendJsonRequest(
        string $presenterName,
        string $actionName,
        string $method = IRequest::GET,
        array $parameters = [],
        array $body = []
    ): array
    {
        $parameters['action'] = $actionName;

        /** @var IPresenterFactory $presenterFactory */
        $presenterFactory = $this->container->getByType(IPresenterFactory::class);
        $presenter        = $presenterFactory->createPresenter($presenterName);
        $request          = $this->getProperty($presenter, 'httpRequest');
        $this->setProperty($request, 'rawBodyCallback', function () use ($body) {
            return Json::encode($body);
        });
        $this->setProperty($presenter, 'httpRequest', $request);

        $response = $presenter->run(new Request($presenterName, $method, $parameters));

        if ($response instanceof JsonResponse) {
            return $response->getPayload();
        }

        throw new LogicException('Response must be JSON!"');
    }

}