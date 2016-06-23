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
use LanguagesControllerInstalled;

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

  protected function getCid($lang)
  {
    $document = new \DOMDocument();
    $document->load('./cache/languages.xml');

    $documentXpath = new \DOMXPath($document);
    $langNodes = $documentXpath->query('/extensionset/extension[@name=\''.$lang.'\']');
    $langNode = $langNodes->item(0);
    return mb_substr($langNode->getAttribute('element'),4);
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
    if( !$this->installLanguagePacks($languagesArray,$input->getArgument('site')))
    {
      throw new \RuntimeException(sprintf('Couldn\'t install languages.', $languages));
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
    $languageList->load('./cache/languages.xml');

    $langInfoString = '';

    foreach($languageList->getElementsByTagName('extension') as $languageLine)
    {

      if($languageLine->getAttribute('name') == $language){
        $langInfoString = $languageLine->getAttribute('detailsurl');
        break;
      }
    }
    var_dump($langInfoString);
    if(empty($langInfoString))
    {
      throw new \RuntimeException('Language %s not in list.', $language);
    }

    if(!$this->_downloadFile('./cache/'.$languageLine->getAttribute('name').'_langinfo.xml',$langInfoString))
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
    $languageInfoXML->load('./cache/'.$language.'_langinfo.xml');
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

    $this->_downloadFile('./cache/'.$language.'_pack.zip',$maxItem->getElementsByTagName('downloadurl')->item(0)->nodeValue);

    return true;
  }

  private function checkZipDir($path)
  {
    $zip = zip_open($path);
    $dirs = array();
    while($zip_entry = zip_read($zip))
    {

      if( 0 == zip_entry_filesize($zip_entry))
      {
        $dirs[] = zip_entry_name($zip_entry);
      }
    }

    if( !empty($dirs) ){
      return true;
    }

    return false;
  }

  private function unpackToFolders($lang,$cid,$site)
  {
    $zipArchive = new \ZipArchive();
    $zipArchive->open(sprintf('cache/%s_pack.zip',$lang));

    exec('mkdir -p cache/'.$lang.'_pack');
    $zipArchive->extractTO('cache/'.$lang.'_pack');

    $dir = scandir('cache/'.$lang.'_pack/');
    $copyRest = false;

    foreach($dir as $key => $filename)
    {
      if( $filename == '.' or $filename == '..' )
      {
        continue;
      }

      if(preg_match('/.*admin.*\.zip/', $filename ) and $this->checkZipDir('cache/'.$lang.'_pack/'.$filename))
      {
        //CZ admin verze
        $zipArchive->open('cache/'.$lang.'_pack/'.$filename);
        $zipArchive->extractTo('cache/'.$lang.'_pack/');
        $copyRest = true;
      }elseif(preg_match('/.*admin.*\.zip/', $filename ) and !$this->checkZipDir('cache/'.$lang.'_pack/'.$filename)){
        //SK admin verze
        $zipArchive->open('cache/'.$lang.'_pack/'.$filename);
        $zipArchive->extractTo('application/'.$site.'/administrator/language/'.$cid.'/');
      }

      if(preg_match('/.*site.*\.zip/', $filename) and $this->checkZipDir('cache/'.$lang.'_pack/'.$filename))
      {
        //CZ site verze
        $zipArchive->open('cache/'.$lang.'_pack/'.$filename);
        $zipArchive->extractTo('cache/'.$lang.'_pack/');
        $copyRest = true;
      }elseif(preg_match('/.*site.*\.zip/', $filename )  and !$this->checkZipDir('cache/'.$lang.'_pack/'.$filename)){
        //SK site verze
        $zipArchive->open('cache/'.$lang.'_pack/'.$filename);
        $zipArchive->extractTo('application/'.$site.'/language/'.$cid.'/');
      }
    }

    if( $this->checkZipDir(sprintf('cache/%s_pack.zip',$lang)) OR $copyRest)
    {
      // presunout slozky na mista
      // admin_xx-XX
      $adminFiles = scandir('cache/'.$lang.'_pack/admin_'.$cid.'/');
      exec('mkdir -p application/'.$site.'/administrator/language/' . $cid . '/');
      foreach($adminFiles as $key => $filename)
      {
        if( !is_dir('cache/'.$lang.'_pack/admin_'.$cid.'/'.$filename) ) {
          copy('cache/' . $lang . '_pack/admin_' . $cid . '/' . $filename, 'application/'.$site.'/administrator/language/' . $cid . '/' . $filename);
        }
      }
      // site_xx-XX
      $siteFiles = scandir('cache/'.$lang.'_pack/site_'.$cid.'/');
      exec('mkdir -p application/'.$site.'/language/' . $cid . '/');
      foreach($siteFiles as $key => $filename)
      {
        if( !is_dir('cache/'.$lang.'_pack/site_'.$cid.'/'.$filename)) {
          copy('cache/' . $lang . '_pack/site_' . $cid . '/' . $filename, 'application/'.$site.'/language/' . $cid . '/' . $filename);
        }
      }
    }

    return true;
  }

  public function installLanguagePacks($langs, $site)
  {
    foreach($langs as $lang)
    {
      $cid = $this->getCid($lang);
      $this->unpackToFolders($lang, $cid, $site);
    }

    if(!$this->installLanguagePacksDiscover($site))
    {
      return false;
    }

    return true;
  }

  public function installLanguagePacksDiscover($site)
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
      if(!$installer->discover_install($result->extension_id))
      {
        return false;
      }
    }
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