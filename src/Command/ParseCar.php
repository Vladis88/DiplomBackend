<?php

namespace App\Command;

use App\Entity\CarPost;
use App\Service\CarPostCrawlerService;
use App\Service\CarPostService;
use App\Service\SubscriptionResolverService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ParseCar
 * @package App\Command
 */
class ParseCar extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:parse-car';

    /**
     * @var CarPostService
     */
    private CarPostService $carPostService;

    /**
     * @var CarPostCrawlerService
     */
    private CarPostCrawlerService $carPostCrawlerService;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private Filesystem $symfonyFilesystem;

    /**
     * @var SubscriptionResolverService
     */
    private SubscriptionResolverService $subscriptionResolverService;

    /**
     * @var array
     */
    private array $resultCars = [];

    /**
     * ParseCar constructor.
     * @param \Symfony\Component\Filesystem\Filesystem $symfonyFilesystem
     * @param CarPostService $carPostService
     * @param CarPostCrawlerService $carPostCrawlerService
     * @param SubscriptionResolverService $subscriptionResolverService
     */
    public function __construct(
        Filesystem                  $symfonyFilesystem,
        CarPostService              $carPostService,
        CarPostCrawlerService       $carPostCrawlerService,
        SubscriptionResolverService $subscriptionResolverService
    )
    {
        parent::__construct();

        $this->symfonyFilesystem = $symfonyFilesystem;
        $this->carPostService = $carPostService;
        $this->carPostCrawlerService = $carPostCrawlerService;
        $this->subscriptionResolverService = $subscriptionResolverService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Parse posts of cars from av.by');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // fix start time for parsing
        $executionStartTime = microtime(true);

        $this->carPostCrawlerService->fillCarLinks();

        foreach ($this->carPostCrawlerService->getCurrentCarLinks() as $url) {
            dump($url);
            $array = $this->carPostCrawlerService->extract($url);
            $array['link'] = $url;
            $this->resultCars[] = $array;
        }
        dump($this->resultCars); exit();
        $posts = $this->carPostService->save($this->resultCars);
        $this->carPostService->saveImages($posts);

        $this->resolveSubscriptions($posts);

        $executionEndTime = microtime(true);

        // Result time of executing script
        $seconds = $executionEndTime - $executionStartTime;
        echo "\nThis script took $seconds to execute.\n";
    }

    /**
     * @param array $posts
     */
    private function resolveSubscriptions(array $posts)
    {
        /** @var CarPost $post */
        foreach ($posts as $post) {
            $this->subscriptionResolverService->resolveSubscription($post);
        }
    }
}
