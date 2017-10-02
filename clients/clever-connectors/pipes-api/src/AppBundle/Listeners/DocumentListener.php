<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Tomas Sedlacek
 * Mail: mail@kedlas.cz
 * Date: 29/09/2017
 * Time: 11:18
 */

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;

/**
 * TODO - DRY - pf-bundles/src/Authorization/DocumentListener/DocumentListener.php
 * TODO - replace these listeners by crypting implementation inside Document classes on preFlush and postLoad events
 *
 * Class DocumentListener
 *
 * @package CleverConnectors\AppBundle\Listeners
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

            /** @var SystemInstall $document */
            foreach ($documents as $document) {
                if ($this->isSystemInstall($document)) {
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

        /** @var SystemInstall $document */
        if ($this->isSystemInstall($document)) {
            $document->setSettings($this->cryptManager->decrypt($document->getEncryptedSettings()));
        }
    }

    /**
     * @param mixed $document
     *
     * @return bool
     */
    private function isSystemInstall($document): bool
    {
        return $document instanceof SystemInstall;
    }

}