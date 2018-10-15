<?php

namespace App\Presenters;

use App\Forms\PublishFormFactory;
use App\Forms\SubscribeFormFactory;
use Bunny\Client;
use CmStream\Subscriber;
use Nette\Forms\Form;

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
     * @var PublishFormFactory
     */
    private $publishFormFactory;

    /**
     * @var SubscribeFormFactory
     */
    private $subscribeFormFactory;

    /**
     * StreamPresenter constructor.
     *
     * @param Subscriber           $subscriber
     * @param PublishFormFactory   $publishGeneratorFactory
     * @param SubscribeFormFactory $subscribeFormFactory
     */
    public function __construct(Subscriber $subscriber, PublishFormFactory $publishGeneratorFactory,
                                SubscribeFormFactory $subscribeFormFactory)
    {
        parent::__construct();
        $this->subscriber           = $subscriber;
        $this->publishFormFactory   = $publishGeneratorFactory;
        $this->subscribeFormFactory = $subscribeFormFactory;
    }

    /**
     * @throws \CmStream\Exception\SubscriberException
     * @throws \Nette\Application\AbortException
     */
    public function handleSubscribe(): void
    {
        $token = $this->subscriber->subscribe($this->userId);

        $this->sendJson(['token' => $token]);
    }

    /**
     * Action after logout
     *
     * @param $token
     *
     * @throws \CmStream\Exception\SubscriberException
     * @throws \Nette\Application\AbortException
     */
    public function handleUnSubscribe($token): void
    {
        $this->subscriber->unsubscribe($token);

        $this->sendJson([]);
    }

    /**
     * @throws \CmStream\Exception\SubscriberException
     * @throws \Nette\Application\AbortException
     */
    public function actionSubscribeDemo(): void
    {
        $data = json_decode($this->getHttpRequest()->getRawBody(), TRUE);

        $token = $this->subscriber->subscribe($data['userId'], explode(',', $data['groups']));

        $this->sendJson(['token' => $token]);
    }

    /**
     * @throws \CmStream\Exception\SubscriberException
     * @throws \Nette\Application\AbortException
     */
    public function actionUnsubscribeDemo(): void

    {
        $data = json_decode($this->getHttpRequest()->getRawBody(), TRUE);

        $this->subscriber->unsubscribe($data['token']);

        $this->sendJson([]);
    }

    /**
     * @return Form
     */
    protected function createComponentSubscribeForm()
    {
        $form = $this->subscribeFormFactory->create();

        if ($this->userId) {
            $form['user_id']->setDefaultValue($this->userId);
        }

        return $form;
    }

    /**
     * @return Form
     */
    protected function createComponentPublishForm()
    {
        $form = $this->publishFormFactory->create();
        $form->getElementPrototype()->appendAttribute('class', 'ajax');
        $form->onSuccess[] = [$this, 'processPublishForm'];

        return $form;
    }

    /**
     * @param Form $form
     *
     * @throws \Exception
     */
    public function processPublishForm(Form $form)
    {
        $data = $form->getValues(TRUE);

        $event = $data['event'];

        $client = new Client($this->context->getParameters()['rabbit-mq']);
        $client
            ->connect()
            ->channel()
            ->publish(
                json_encode([
                    'event'   => $event,
                    'content' => json_decode($data['content'], true),
                    'groups'  => explode(',', $data['groups']),
                ]),
                [
                    'content-type' => 'application/json',
                ],
                '',
                $this->template->host      = $this->context->getParameters()['stream']['queue']
            );

        //$form->reset();
        $form->setDefaults(['event' => $event]);

        $this->flashMessage('Message was published.');
        $this->redrawControl('flashMessages');
        $this->redrawControl('publishForm');
    }

}
