<?php
namespace Joomlatools\Console\Command\Language;

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
    $this->setName('language:list')
      ->setDescription('List all usable languages')''
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dest = './';
    $pack = 'http://update.joomla.org/language/translationlist_3.xml'; // version 3.x

    try {
      if (!$this->_downloadLanguageList($dest, $pack))
      {
        throw new \Exception('Could not download language list XML.');
      }
    }catch (\Exception $e)
    {
      $this->_downloadLanguageList($dest, $pack);
    }
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