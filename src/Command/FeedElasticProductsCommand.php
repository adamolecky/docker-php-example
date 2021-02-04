<?php

namespace App\Command;

use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FeedProductsCommand.
 */
class FeedElasticProductsCommand extends Command
{
    protected static $defaultName = 'feed_elastic_products';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $productFilePath;

    /**
     * @var array
     */
    private $indexDefinition;

    /**
     * FeedProductsCommand constructor.
     */
    public function __construct(Client $client, string $productFilePath = '/app/data/products.csv', array $indexDefinition = ['index' => 'elastic'])
    {
        $this->client = $client;
        $this->productFilePath = $productFilePath;
        $this->indexDefinition = $indexDefinition;
        parent::__construct(null);
    }

    /**
     * Command configuration setup.
     */
    protected function configure()
    {
        $this
            ->setDescription('Feed products to Elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('CREATING INDEX....');
        $this->createIndex();

        $io->note('FEEDING INDEX....');
        $this->feedData();

        $io->success('FEEDING DONE');

        return 0;
    }

    /**
     * Parse csv and feed the data to Elasticsearch.
     */
    private function feedData(): void
    {
        $productFile = new \SplFileObject($this->productFilePath);
        $productFile->fgetcsv(); //ignore headline

        while ($data = $productFile->fgetcsv()) {
            list($rowIdx, $content) = $data;
            $doc = array_merge(
                $this->indexDefinition,
                [
                    'id' => $rowIdx,
                    'body' => [
                        'content' => $content,
                    ],
                ]
            );
            $this->client->index($doc);
        }
    }

    /**
     * Creates index with mapping and analyzer.
     */
    private function createIndex(): void
    {
        if ($this->client->indices()->exists($this->indexDefinition)) {
            $this->client->indices()->delete($this->indexDefinition);
        }

        $this->client->indices()->create(
            array_merge(
                $this->indexDefinition,
                [
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 1,
                            'number_of_replicas' => 0,
                            'analysis' => [
                                'analyzer' => [
                                    'autocomplete' => [
                                        'tokenizer' => 'autocomplete',
                                        'filter' => ['lowercase'],
                                    ],
                                ],
                                'tokenizer' => [
                                    'autocomplete' => [
                                        'type' => 'edge_ngram',
                                        'min_gram' => 2,
                                        'max_gram' => 20,
                                        'token_chars' => [
                                            'letter',
                                            'digit',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'mappings' => [
                            'properties' => [
                                'content' => [
                                    'type' => 'text',
                                    'analyzer' => 'autocomplete',
                                    'search_analyzer' => 'standard',
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );
    }
}
