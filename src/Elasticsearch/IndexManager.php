<?php

namespace Doop\ElasticsearchBundle\Elasticsearch;

use Elasticsearch\Client;

class IndexManager
{
    private array $indices;
    private Client $client;

    public function __construct(
        array $indices,
        Client $client
    ) {
        $this->indices = $indices;
        $this->client = $client;
    }

    public function buildIndices(): void
    {
        $indices = $this->client->cat()->indices();
        $indexConfig = $this->indices;
        foreach ($indices as $index) {
            if (isset($indexConfig[$index['index']])) {
                unset($indexConfig[$index['index']]);
            }
        }
        foreach ($indexConfig as $name => $config) {
            $this->client->indices()->create([
                'index' => $name,
                'body' => $config['body'],
            ]);
        }
    }

    public function rebuildIndices(): void
    {
        $indices = $this->client->cat()->indices();
        $indexConfig = $this->indices;
        foreach ($indices as $index) {
            if (isset($indexConfig[$index['index']])) {
                $this->client->indices()->delete(['index' => $index['index']]);
            }
        }
        $this->buildIndices();
    }
}
