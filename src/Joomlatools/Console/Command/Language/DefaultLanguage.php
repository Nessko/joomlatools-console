<?php
namespace Joomlatools\Console\Command\Language;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site;
use Joomlatools\Console\Command\Database;
use Joomlatools\Console\Joomla\Bootstrapper;

use JFactory;
use LanguagesControllerInstalled;

class DefaultLanguage extends Command
{
  protected function configure()
  {
    parent::configure();
    $this->setName('language:default')
      ->setDescription('Install a language pack')
      ->addArgument(
        'default',
        InputArgument::REQUIRED,
        'Language to be set as default'
      )
      ->addArgument(
        'site',
        InputArgument::REQUIRED,
        'Alphanumeric site name. Also used in the site URL with .dev domain'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $langCID = $this->getCid($input->getArgument('default'));
    $this->setDefaultLang($langCID, $input->getArgument('site'));
  }

  protected function getCid($lang)
  {
    $document = new \DOMDocument();
    $document->load('cache/languages.xml');

    $documentXpath = new \DOMXPath($document);
    $langNodes = $documentXpath->query('/extensionset/extension[@name=\''.$lang.'\']');
    $langNode = $langNodes->item(0);
    return mb_substr($langNode->getAttribute('element'),4);
  }

  protected function setDefaultLang($langCID, $site)
  {
    $app = Bootstrapper::getApplication('./application/'.$site.'/');
    ob_start();
    $language = JFactory::getLanguage();
    $newLang = \JLanguage::getInstance($langCID);
    $newLang->setDefault($langCID);
    var_dump($language);
    JFactory::$language = $newLang;
    JFactory::getApplication()->loadLanguage($newLang);
    //$newLang->load('com_languages', JPATH_ADMINISTRATOR);

    $installed = new \LanguagesControllerInstalled();
    $model = $installed->getModel('installed');
    $model->switchAdminLanguage($langCID);
    ob_end_flush();
  }
}