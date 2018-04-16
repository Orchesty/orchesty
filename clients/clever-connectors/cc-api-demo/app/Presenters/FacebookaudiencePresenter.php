<?php declare(strict_types=1);

namespace App\Presenters;

use CleverCore\SocialMultichannel\Handlers\FacebookaudienceHandler;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Presenter;
use Ublaboo\ApiRouter\ApiRoute;

/**
 * Class FacebookaudiencePresenter
 *
 * @package App\Presenters
 *
 * @ApiRoute("/")
 */
class FacebookaudiencePresenter extends Presenter
{

    /**
     * @var FacebookaudienceHandler
     */
    private $handler;

    /**
     * FacebookaudiencePresenter constructor.
     *
     * @param FacebookaudienceHandler $handler
     */
    public function __construct(FacebookaudienceHandler $handler)
    {
        parent::__construct();
        $this->handler = $handler;
    }

    /**
     * @ApiRoute("/api-demo/fb/<clientId>/ad/<adId>/update/state", method="POST")
     *
     * @param string $clientId
     * @param string $adId
     *
     * @throws AbortException
     */
    public function actionUpdateAdStatus(string $clientId, string $adId): void
    {
        try {
            $this->handler->updateStatus($clientId, $adId, json_decode($this->getHttpRequest()->getRawBody(), TRUE));
        } catch (ORMException $e) {
            $this->sendJsonResponse($e->getMessage(), 400);
        }

        $this->sendJsonResponse();
    }

    /**
     * @ApiRoute("/api-demo/fb/<clientId>/ad/getUnprocessed", method="POST")
     *
     * @param string $clientId
     *
     * @throws AbortException
     */
    public function actionGetUnprocessed(string $clientId)
    {
        $this->sendJsonResponse($this->handler->getUnprocessed($clientId));
    }

    /**
     * @param array $data
     * @param int   $code
     *
     * @throws AbortException
     */
    private function sendJsonResponse(array $data = [], int $code = 200): void
    {
        $this->getHttpResponse()->setCode($code);
        $this->sendJson($data);
    }
}

