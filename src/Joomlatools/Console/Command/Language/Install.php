<?php

namespace Joomlatools\Console\Command\Language;

use Joomlatools\Console\Joomla\Bootstrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;

class Install extends Command
{
  
  protected function configure()
  {
    parent::configure();
    $this->setName('language:install')
      ->setDescription('Install a language pack')
      ->addArgument(
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
    $this->installLanguagePack();
  }

  public function installLanguagePack($lang)
  {
    $app = Bootstrapper::bootstrap($this->target_dir);

    ob_start();

    $model = $this->getModel('languages');

    // Get array of selected languages
    $lids = $this->input->get('cid', array(), 'array');
    JArrayHelper::toInteger($lids, array());
    $lids = 46;

    $model->install($lids);
    
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