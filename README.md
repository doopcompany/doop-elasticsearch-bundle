## Doop Elasticsearch bundle

Small bundle for allowing specific Elasticsearch indexes with mapped properties in Sulu.

## Installing

Install the bundle using composer:

`$ composer require doopcompany/doop-elasticsearch-bundle`

Register the plugin to your `config/bundles.php`

```php
<?php

return [
    // ...
    Doop\ElasticsearchBundle\DoopElasticsearchBundle::class => ['all' => true],
];
```

## Configuring

Add a file `doop_elasticsearch.yaml` to your config/packages directory:

```yaml
# EXAMPLE:
doop_elasticsearch:
  indices:
      my_index:
        body:
          settings:
            number_of_shards: 2
            number_of_replicas: 1
          mappings:
            properties:
              date:
                type: date
              geo_point:
                type: geo_point
```

This will allow the bundle to create an index with some mapped properties by default.
These will be created automatically using hooks on the MassiveSearchBundle.
This behavior can be disabled by adding the following to your `doop_elasticsearch` config:
```yaml
doop_elasticsearch:
  massive_search_hooks_enabled: false
```

## Indexing documents
Because the massive search bundle does not allow you the freedom to configure indexes and mappings yourself, this bundle has been introduced.
You can still use events fired by massive search to index documents in your custom indices. For instance, with an event subscriber:
```php
<?php

namespace App\EventListener;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Massive\Bundle\SearchBundle\Search\Event\PreDeindexEvent;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\SearchBundle\Search\Document;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyArticleMassiveSearchSubscriber implements EventSubscriberInterface
{
    private DocumentManagerInterface $documentManager;
    private Client $client;

    public function __construct(
        DocumentManagerInterface $documentManager,
        Client $client
    ) {
        $this->documentManager = $documentManager;
        $this->client = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SearchEvents::PRE_INDEX => 'onPreIndex',
            SearchEvents::PRE_DEINDEX => 'onPreDeIndex',
        ];
    }

    public function onPreDeIndex(PreDeindexEvent $event)
    {
        /** @var Document $document */
        $document = $event->getDocument();
        if (ArticleDocument::class !== $document->getClass() || 'my_article' !== $document->getField('_structure_type')->getValue()) {
            return;
        }
        try {
            $this->client->delete([
                'index' => 'my_index',
                'id' => $document->getId(),
            ]);
        } catch (Missing404Exception) {
        }
    }

    /**
     * @throws DocumentManagerException
     */
    public function onPreIndex(PreIndexEvent $event)
    {
        /** @var Document $document */
        $document = $event->getDocument();
        if (ArticleDocument::class !== $document->getClass() || 'my_article' !== $document->getField('_structure_type')->getValue()) {
            return;
        }

        /** @var ArticleDocument $article */
        $article = $this->documentManager->find($document->getId());
        $structure = $article->getStructure()->toArray();
        $extensions = $article->getExtensionsData()->toArray();

        $this->client->index([
            'index' => 'my_index',
            'id' => $document->getId(),
            'body' => [
                'id' => $document->getId(),
                'title' => $structure['title'],
                'date' => $structure['date'] ?? null,
                'geo_point' => $structure['geo_point'] ?? null,
                'route_path' => $structure['routePath'],
                'description' => $extensions['excerpt']['description'],
                'excerpt' => [
                    'categories' => $this->formatCategories($extensions['excerpt']['categories']),
                ],
            ],
        ]);
    }

    private function formatCategories(array $categoryIds): array
    {
        $formatted = [];

        foreach ($categoryIds as $categoryId) {
            $formatted[] = ['id' => $categoryId];
        }

        return $formatted;
    }
}
```

Finally, you can search the index using native Elasticsearch queries.