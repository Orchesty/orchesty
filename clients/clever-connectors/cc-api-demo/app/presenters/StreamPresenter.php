<?php

namespace App\Presenters;

/**
 * Class StreamPresenter
 *
 * @package App\Presenters
 */
class StreamPresenter extends BasePresenter
{

    /**
     * StreamPresenter constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function renderDefault()
    {
        $this->template->host = $this->context->getParameters()['ws']['host'];
    }

}
