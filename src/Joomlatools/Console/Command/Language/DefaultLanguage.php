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
use JLanguage;
use InstallationModelLanguages;
use JLoader;

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
    include_once('application/'.$site.'/_installation/model/languages.php');
    $model = new InstallationModelLanguages;
    $model->setDefault($langCID);
    $model->setDefault($langCID, 'site');
    ob_end_flush();
  }
}