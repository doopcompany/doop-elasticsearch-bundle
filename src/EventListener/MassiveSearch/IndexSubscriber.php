<?php

namespace Doop\ElasticsearchBundle\EventListener\MassiveSearch;

use Doop\ElasticsearchBundle\Elasticsearch\IndexManager;
use Massive\Bundle\SearchBundle\Command\ReindexCommand;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IndexSubscriber implements EventSubscriberInterface
{
    private bool $indicesCreated = false;
    private IndexManager $indexManager;

    public function __construct(
        IndexManager $indexManager
    ) {
        $this->indexManager = $indexManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            SearchEvents::PRE_INDEX => ['onIndex', 2048],
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof ReindexCommand) {
            $this->indicesCreated = true;
            $this->indexManager->rebuildIndices();
        }
    }

    public function onIndex()
    {
        $this->createIndices();
    }

    private function createIndices(): void
    {
        if (true === $this->indicesCreated) {
            return;
        }
        $this->indexManager->buildIndices();
        $this->indicesCreated = true;
    }
}
