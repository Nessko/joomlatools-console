<?php
namespace Joomlatools\Console\Command\Language;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListAll extends Command
{
  protected function configure()
  {
    parent::configure();
    $this->setName('language:list')
      ->addOption(
        'download-only',
        null,
        InputOption::VALUE_NONE,
        'Downloads only the info file.'
      )
      ->setDescription('List all usable languages');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    mkdir('cache');
    $dest = './cache/languages.xml';
    $pack = 'http://update.joomla.org/language/translationlist_3.xml'; // version 3.x

    $this->downloadLanguageList($dest,$pack);

    $rows = $this->parseList($dest);

    $table = new Table($output);
    $table
      ->setHeaders(array('Language', 'Joomla! version', 'pckg', 'url'))
      ->setRows($rows);
    $table->render();
  }

  protected function parseList($fileXML)
  {
    $languageXML = new \DOMDocument();
    $languageXML->load($fileXML);

    $languagesNodes = $languageXML->documentElement;

    $rows = array();
    foreach ($languagesNodes->getElementsByTagName('extension') as $language) {
      $rows[] = array(
        $language->getAttribute('name'),
        $language->getAttribute('version'),
        $language->getAttribute('element'),
        $language->getAttribute('detailsurl')
      );
    }

    return $rows;
  }

  public function downloadLanguageList($dest = 'languages.xml',$pack = 'http://update.joomla.org/language/translationlist_3.xml', $cacheDir = './cache/')
  {
    try {
      if (!$this->_downloadLanguageList($cacheDir.$dest, $pack)) {
        throw new \Exception('Could not download language list XML.');
      }
    } catch
    (\Exception $e) {
      $this->_downloadLanguageList($cacheDir.$dest, $pack);
    }
  }

  public function _downloadLanguageList($dest, $pack)
  {
    $bytes = file_put_contents($dest, fopen($pack, 'r'));

    if ($bytes === false || $bytes == 0) {
      return false;
    }

    return true;
  }
}