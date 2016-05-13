<?php

namespace Joomlatools\Console\Command\Site;

use Joomlatools\Console\Joomla\Bootstrapper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;

class Language extends Site\AbstractSite
{
  
  protected function configure()
  {
    parent::configure();
    $this->setName('language:install')
      ->setDescription('Install a language pack')
      ->addOption(
        'install',
        null,
        InputOption::VALUE_REQUIRED,
        'A comma separated list of languages to install'
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

  public function installLanguagePack($lang)
  {
    $app = Bootstrapper::bootstrap($this->target_dir);

    ob_start();

    ob_end_flush();
  }

  public function _downloadLanguagePack($dest, $list){
    $bytes = file_put_contents($dest, fopen($list));

    if ($bytes === false || $bytes == 0) {
      return false;
    }

    return true;
  }
}