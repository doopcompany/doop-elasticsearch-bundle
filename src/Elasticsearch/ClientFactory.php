<?php

namespace Doop\ElasticsearchBundle\Elasticsearch;

use Elasticsearch\ClientBuilder;

class ClientFactory
{
    public static function create(array $hosts)
    {
        return ClientBuilder::create()->setHosts($hosts)->build();
    }
}