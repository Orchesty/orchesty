<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 3:06 PM
 */

namespace Hanaboso\PipesFramework\Authorization\DocumentListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;

/**
 * Class DocumentListener
 *
 * @package Hanaboso\PipesFramework\Authorization\EntityListener
 */
class DocumentListener
{

    /**
     * @var CryptManager
     */
    private $cryptManager;

    /**
     * DocumentListener constructor.
     *
     * @param CryptManager $cryptManager
     */
    function __construct(CryptManager $cryptManager)
    {
        $this->cryptManager = $cryptManager;
    }

    /**
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event): void
    {
        for ($i = 0; $i <= 1; $i++) {
            $documents = $i
                ? $event->getDocumentManager()->getUnitOfWork()->getScheduledDocumentUpdates()
                : $event->getDocumentManager()->getUnitOfWork()->getScheduledDocumentInsertions();

            /** @var Authorization $document */
            foreach ($documents as $document) {
                if ($this->isAuthorization($document)) {
                    $document->setEncryptedToken($this->cryptManager->encrypt($document->getToken()));
                    $document->setEncryptedSettings($this->cryptManager->encrypt($document->getSettings()));
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $document = $event->getDocument();

        /** @var Authorization $document */
        if ($this->isAuthorization($document)) {
            $document->setToken($this->cryptManager->decrypt($document->getEncryptedToken()));
            $document->setSettings($this->cryptManager->decrypt($document->getEncryptedSettings()));
        }
    }

    /**
     * @param mixed $document
     *
     * @return bool
     */
    private function isAuthorization($document): bool
    {
        return $document instanceof Authorization;
    }

}