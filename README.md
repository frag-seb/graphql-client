PHP Client for [GraphQL](http://graphql.org/)

```php
<?php 

declare(strict_types = 1);

use FragSeb\GraphQL\Client;
use FragSeb\GraphQL\Transformer\DataTransformerInterface;
use GuzzleHttp\Client as GuzzleClient;

set_time_limit(0);

require __DIR__ . '/../vendor/autoload.php';


$client = new Client(new GuzzleClient([
    'base_uri' => 'https://example.com/api/v1/graphql'
]));


$query = <<<'QUERY'
    query Foo($id: String!) {
        bar(id: $id) {
            id
            name
            sub {
                id
            }
        }
    }
QUERY;

$variables = [
    'id' => 'test',
];

$response = $client->query($query, $variables);

var_dump($response->getData());

$transformer = function (string $key) {
    return new class ($key) implements DataTransformerInterface {

        /**
         * @var string
         */
        private $key;

        public function __construct(string $key)
        {
            $this->key = $key;
        }

        public function transform(array $data): array
        {
            return $data[$this->key];
        }
    };
};

var_dump($response->getData($transformer('data')));
var_dump($response->getData($transformer('extensions')));

```
