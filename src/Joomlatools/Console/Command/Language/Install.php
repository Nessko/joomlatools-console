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
    $languages = $input->getArgument('languages');

    $languagesArray = explode(',',$languages);

    if(!file_exists('./languages.xml'))
    {

      $command = new ListAll();
      $command->downloadLanguageList();
    }

    if(!$this->downloadLanguagePacksInfo($languagesArray))
    {
      throw new \RuntimeException('Couldn\'t download language info files.');
    }

    $this->downloadLanguagePacks($languagesArray);
    $this->installLanguagePack($languagesArray,$input->getArgument('site'));
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
    $languageList->load('./languages.xml');

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

    if(!$this->_downloadFile('./'.$languageLine->getAttribute('name').'_langinfo.xml',$langInfoString))
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
    $languageInfoXML->load('./'.$language.'_langinfo.xml');

    $languageNodes = $languageInfoXML->getElementsByTagName('extension');
    $languageInfo = array();



    foreach($languageNodes as $languageNode)
    {
      if(in_array($languageNode->getAttribute('name'),$languages))
      {
        if(!$this->_downloadFile(
          './'.$languageNode->getAttribute('name'),
          $languageNode->getAttribute('detailsurl')
        ))
        {
          throw new \RuntimeException(sprintf('Couldn\'t download language info file for %s language!', $languageNode->getAttribute('name')));
        }

        $docXML = new \DOMDocument();
        $docXML->load('./'.$languageNode->getAttribute('name'));

        $updateList = $docXML->getElementsByTagName('update');

        foreach($updateList as $update)
        {
          $nameNode = $update->getElementsByTagName('name');
          if($nameNode->nodeValue == $languageNode->getAttribute('name'))
          {
            $downloadsNode = $update->getElementsByTagName('downloads');
            $downloadURLNode = $downloadsNode->getElementByTagName('downloadurl');

            if( !$this->_downloadFile('./'.$languageNode->getAttribute('name').'.zip',$downloadURLNode->nodeValue))
            {
              throw new \RuntimeException(sprintf('Couldn\'t download language pack for %s language!', $languageNode->getAttribute('name')));
            }
          }
        }
      }
    }
  }

  public function installLanguagePack($lang,$site)
  {
    return true;
    $app = Bootstrapper::bootstrap($site);

    ob_start();
    
    ob_end_flush();
  }

  public function _downloadFile($dest, $list){
    $bytes = file_put_contents($dest, fopen($list,'R'));

    if ($bytes === false || $bytes == 0) {
      return false;
    }

    return true;
  }
}