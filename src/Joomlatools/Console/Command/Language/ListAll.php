<?php
namespace Joomlatools\Console\Command\Site;

use Symfony\Component\Console\Command\Command
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListAll extends Command
{
  protected function configure()
  {
    parent::configure();
    $this->setName('site:list')
      ->setDescription('List all usable languages')''
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

  }
  
  public function _downloadLanguageList($dest, $pack)
  {
    $bytes = file_put_contents($dest, fopen($pack,'r'));

    if ($bytes === false || $bytes == 0) {
      return false;
    }

    return true;
  }
}