<?php

namespace AppBundle\Command;

use AppBundle\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

class GrabFeeds extends ContainerAwareCommand
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * GrabFeeds constructor.
     *
     * @param null $name
     * @param EntityManagerInterface $em
     */
    public function __construct($name = null, EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('feedsreader:grab-feeds')
            ->setDescription('Get feeds from urls')
            ->setHelp('This command get feeds from urls and save them to database')
            ->addArgument('urls', InputArgument::REQUIRED, 'Please input urls of feeds(separated by commas)?');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get("monolog.logger.feedsreader");
        $urls = explode(',', $input->getArgument('urls'));
        $validUrls = [];

        // Validate urls
        foreach ($urls as $url) {
            $validator = Validation::createValidator();
            $violations = $validator->validate($url, new Url());

            if (count($violations) === 0) {
                $validUrls[] = $url;
            }
        }

        // Grab content in urls
        foreach ($validUrls as $url) {
            $content = \file_get_contents($url);
            $xml = new \SimpleXMLElement($content);

            foreach ($xml->channel->item as $item) {
                try {
                    $post = new Post();
                    $post->setName($item->title);
                    $post->setDescription($item->description);
                    $post->setExternal(true);
                    $post->setPublicDate(new \DateTime($item->pubDate));
                    $this->em->persist($post);
                    $this->em->flush();
                    $logMessage = 'Added item ' . $item->title;
                    $logger->info($logMessage);
                    $output->writeln($logMessage);
                } catch (\Exception $e) {
                    $logger->error("There are some errors occurred!");
                    $output->writeln("There are some errors occurred!");
                }
            }
        }
    }
}
