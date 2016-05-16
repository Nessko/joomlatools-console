<?php
namespace Joomlatools\Console\Command\Language;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;

class DefaultLanguage
{
  protected function configure()
  {
    parent::configure();
    $this->setName('language:default')
      ->setDescription('Install a language pack')
      ->addArgument(
        'default',
        InputOption::REQUIRED,
        'Language to be set as default'
      )
      ->addArgument(
        'site',
        InputArgument::REQUIRED,
        'Alphanumeric site name. Also used in the site URL with .dev domain'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

  }
}