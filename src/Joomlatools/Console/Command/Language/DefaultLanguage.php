<?php
namespace Joomlatools\Console\Command\Site;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;

class DefaultLanguage
{
  protected function configure()
  {
    parent::configure();
    $this->setName('language:default')
      ->setDescription('Install a language pack')
      ->addOption(
        'default',
        null,
        InputOption::VALUE_REQUIRED,
        'Language to be set as default'
      )
      ->addOption(
        'site',
        null,
        InputOption::VALUE_REQUIRED,
        'Site to apply to'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

  }
}