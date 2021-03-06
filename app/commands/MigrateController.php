<?php
/**
 * Created by PhpStorm.
 * User: tashik
 * Date: 22.03.16
 * Time: 13:03
 */

namespace app\commands;

use Yii;
use yii\helpers\ArrayHelper;

class MigrateController extends \yii\console\controllers\MigrateController
{
  /**
   * @var array
   */
  public $migrationLookup = [];

  public $migrationPath = '@app/migrations/db';
  /**
   * @var array
   */
  private $_migrationFiles;

  protected function getMigrationFiles()
  {
    if ($this->_migrationFiles === null) {
      $this->_migrationFiles = [];
      $directories = array_merge($this->migrationLookup, [$this->migrationPath]);
      $extraPath = ArrayHelper::getValue(Yii::$app->params, 'yii.migrations');
      if (!empty($extraPath)) {
        $directories = array_merge((array) $extraPath, $directories);
      }

      foreach (array_unique($directories) as $dir) {
        $dir = Yii::getAlias($dir, false);
        if ($dir && is_dir($dir)) {
          $handle = opendir($dir);
          while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
              continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($path)) {
              $this->_migrationFiles[$matches[1]] = $path;
            }
          }
          closedir($handle);
        }
      }

      ksort($this->_migrationFiles);
    }

    return $this->_migrationFiles;
  }

  protected function createMigration($class)
  {
    $file = $this->getMigrationFiles()[$class];
    require_once($file);
    $params = Yii::$app->params;
    return new $class(['db' => $this->db], $params );
  }

  protected function getNewMigrations()
  {
    $applied = [];
    foreach ($this->getMigrationHistory(null) as $version => $time) {
      $applied[$version] = true;
    }

    $migrations = [];
    foreach ($this->getMigrationFiles() as $version => $path) {
      if (!isset($applied[$version])) {
        $migrations[] = $version;
      }
    }

    return $migrations;
  }
}