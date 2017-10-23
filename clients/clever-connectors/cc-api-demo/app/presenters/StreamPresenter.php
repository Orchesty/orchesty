<?php

namespace App\Presenters;

use Bunny\Client;
use CmStream\Subscriber;
use Nette\Forms\Form;
use PublishFormFactory;

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
     * StreamPresenter constructor.
     *
     * @param Subscriber         $subscriber
     * @param PublishFormFactory $publishFormFactory
     */
    public function __construct(Subscriber $subscriber, PublishFormFactory $publishFormFactory)
    {
        parent::__construct();
        $this->subscriber         = $subscriber;
        $this->publishFormFactory = $publishFormFactory;
    }

    /**
     *
     */
    public function renderDefault(): void
    {
        $this->template->host = $this->context->getParameters()['ws']['host'];
    }

    /**
     *
     */
    public function actionSubscribe(): void
    {
        $data = json_decode($this->getHttpRequest()->getRawBody(), TRUE);

        $token = $this->subscriber->subscribe($data['userId'], explode(',', $data['groups']));

        $this->sendJson(['token' => $token]);
    }

    /**
     *
     */
    public function actionUnsubscribe(): void

    {
        $data = json_decode($this->getHttpRequest()->getRawBody(), TRUE);

        $this->subscriber->unsubscribe($data['token']);

        $this->sendJson([]);
    }

    /**
     * @return Form
     */
    protected function createComponentPublishForm()
    {
        $form              = $this->publishFormFactory->create();
        $form->getElementPrototype()->appendAttribute( 'class', 'ajax' );
        $form->onSuccess[] = [$this, 'processPublishForm'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function processPublishForm(Form $form)
    {
        $data = $form->getValues(TRUE);

        $client = new Client($this->context->getParameters()['rabbit-mq']);
        $client
            ->connect()
            ->channel()
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

        if($this->isAjax()) {
            $form->reset();
            $this->terminate();
        }
    }

}
