<?php

namespace Joomlatools\Console\Command\Language;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;
use Joomlatools\Console\Command\Database;
use Joomlatools\Console\Joomla\Bootstrapper;

use JFactory;

class Install extends Database\AbstractDatabase
{
  protected $cacheDir;
  
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
      ->addOption(
        'cache-dir',
        null,
        InputOption::VALUE_REQUIRED,
        'Location for all downloaded files.'
      )
      ->addOption(
        'skip-exists-check',
        null,
        InputOption::VALUE_NONE,
        'Do not check if database already exists or not.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    if($input->getOption('cache-dir'))
    {
      $this->cacheDir = $input->getOption('cache-dir');
      if(!file_exists('./'.$this->cacheDir))
      {
        mkdir('./'.$this->cacheDir);
      }
    }

    $languages = $input->getArgument('languages');

    $languagesArray = explode(',',$languages);

    if(!file_exists('./'.$this->cacheDir.'/languages.xml'))
    {

      $command = new ListAll();
      $command->downloadLanguageList();
    }

    if(!$this->downloadLanguagePacksInfo($languagesArray))
    {
      throw new \RuntimeException('Couldn\'t download language info files.');
    }

    $this->downloadLanguagePacks($languagesArray);
    if( $this->installLanguagePack($languagesArray,$input->getArgument('site')))
    {
      $output->writeln(sprintf('Language %s successfully installed.','Czech'));
    }else
    {
      throw new \RuntimeException(sprintf('Couldn\'t install languages: %s', $languages));
    }

  }

  protected function downloadLanguagePacksInfo($languages)
  {
    foreach($languages as $language)
    {
      if(!$this->_downloadLanguagePackInfo($language))
      {
        throw new \RuntimeException(sprintf('Couln\'t download language info file for %s language.', $language));
      }
    }

    return true;
  }

  protected function _downloadLanguagePackInfo($language){
    $languageList = new \DOMDocument();
    $languageList->load('./'.$this->cacheDir.'/languages.xml');

    $langInfoString = '';

    foreach($languageList->getElementsByTagName('extension') as $languageLine)
    {

      if($languageLine->getAttribute('name') == $language){
        $langInfoString = $languageLine->getAttribute('detailsurl');
        break;
      }
    }

    if(empty($langInfoString))
    {
      throw new \RuntimeException('Language %s not in list.', $language);
    }

    if(!$this->_downloadFile('./'.$this->cacheDir.'/'.$languageLine->getAttribute('name').'_langinfo.xml',$langInfoString))
    {
      throw new \RuntimeException('Couldn\'t download langinfo file for language \'%s\'', $language);
    }

    return true;
  }

  public function downloadLanguagePacks($languages)
  {
    foreach($languages as $language)
    {
      $this->downloadLanguagePack($language);
    }
  }

  public function downloadLanguagePack($language){
    $languageInfoXML = new \DOMDocument();
    $languageInfoXML->load('./'.$this->cacheDir.'/'.$language.'_langinfo.xml');
    $languageInfoXpath = new \DOMXPath($languageInfoXML);

    $lastVersion = $languageInfoXpath->query('/updates/update/version');
    $maxVersion = '0.0.0';
    $maxItem = null;

    foreach($lastVersion as $version)
    {
      if( version_compare($version->nodeValue, $maxVersion, '>') ){
        $maxVersion = $version->nodeValue;
        $maxItem = $version->parentNode;
      }
    }

    $this->_downloadFile('./'.$this->cacheDir.'/'.$language.'_pack.zip',$maxItem->getElementsByTagName('downloadurl')->item(0)->nodeValue);

    return true;
  }

  private function unpackToFolders($lang,$site)
  {

  }

  public function installLanguagePack($lang,$site)
  {
    $app = Bootstrapper::getApplication('./application/'.$site.'/');

    ob_start();
    $installer = $app->getInstaller();
    $installer->discover();

    require_once JPATH_ADMINISTRATOR . '/components/com_installer/models/discover.php';

    $model = new \InstallerModelDiscover();
    $model->discover();

    $results = $model->getItems();

    foreach($results  as $result)
    {
      $installer->discover_install($result->extension_id);
    }

    $language = JFactory::getLanguage();
    $newLang = \JLanguage::getInstance('cs-CZ');
    $newLang->setDefault('cs-CZ');
    JFactory::$language = $newLang;
    $app->loadLanguage($language = $newLang);
    //$newLang->load('com_languages', JPATH_ADMINISTRATOR);

    ob_end_flush();

    return true;
  }

  public function _downloadFile($dest, $list){
    $bytes = file_put_contents($dest, fopen($list,'R'));

    if ($bytes === false || $bytes == 0) {
      return false;
    }

    return true;
  }
}