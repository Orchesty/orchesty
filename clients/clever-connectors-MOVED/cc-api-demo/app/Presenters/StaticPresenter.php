<?php

namespace App\Presenters;

use Nette\Application\Responses;

/**
 * Class StaticPresenter
 *
 * @package App\Presenters
 */
class StaticPresenter extends BasePresenter
{
	public function actionDefault()
	{
		$file = $this->getHttpRequest()->getQuery('file');

		// sanitize the file path
		if (!preg_match('/[^\/][a-zA-Z0-9_\/-]+\.(jpg|png|gif|ico|css|js|txt|html)$/', $file, $matches)) {
			throw new \Exception('Unsafe file path');
		}

		switch ($matches[1]) {
			case 'css':
				$contentType = 'text/css';
				break;
			default:
				$contentType = 'application/octet-stream';
		}

		$this->sendResponse(new Responses\FileResponse($file, null, $contentType));
	}
}
