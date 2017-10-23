<?php

namespace App\Presenters;

use Bunny\Client;
use CmStream\Subscriber;
use Nette\Application\Responses\JsonResponse;
use Nette\Forms\Form;
use Nette\Http\Request;

/**
 * Class StreamPresenter
 *
 * @package App\Presenters
 */
class StreamPresenter extends BasePresenter
{

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * StreamPresenter constructor.
     *
     * @param Subscriber $subscriber
     */
    public function __construct(Subscriber $subscriber)
    {
        parent::__construct();
        $this->subscriber = $subscriber;
    }

    /**
     *
     */
    public function renderDefault()
    {
        $this->template->host = $this->context->getParameters()['ws']['host'];
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function actionSubscription(Request $request)
    {
        $data = json_decode($request->getRawBody(), TRUE);

        $token = $this->subscriber->subscribe($data['userId'], $data['groups']);

        return new JsonResponse(['token' => $token]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function actionUnsubscription(Request $request)
    {
        $data = json_decode($request->getRawBody(), TRUE);

        $this->subscriber->unsubscribe($data['token']);

        return new JsonResponse([]);
    }

    /**
     * @param Form $form
     */
    public function processPublishForm(Form $form)
    {
        $data = $form->getValues(TRUE);

        $client = new Client($this->context->getParameters()['rabbit-mq']);
        $client->channel()
            ->publish(
                json_encode([
                    'event'   => $data['event'],
                    'content' => $data['content'],
                    'groups'  => explode(',', $data['groups']),
                ]),
                [
                    'content-type' => 'application/json',
                ],
                '',
                'stream'
            );
    }

}
