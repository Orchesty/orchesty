<?php

namespace App\Presenters;

use Nette;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->menuItems = [
            'Connectors' => 'Homepage:',
            'Stream' => 'Stream:',
        ];
    }

}
