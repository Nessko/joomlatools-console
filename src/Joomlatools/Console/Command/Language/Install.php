<?php

namespace Joomlatools\Console\Command\Site;

use Joomlatools\Console\Joomla\Bootstrapper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;

class Language extends Site\AbstractSite
{
  
  protected function configure()
  {
    parent::configure();
    $this->setName('language:install')
      ->setDescription('Install a language pack')
      ->addArgiment(
        'languages',
        InputArgument::REQUIRED,
        'A comma separated list of languages to install'
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