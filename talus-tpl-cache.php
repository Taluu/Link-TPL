<?php
/**
 * Contient les fonctions de caches FTP, nécessaires à Talus' TPL.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *      
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *      
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA. 
 *
 * @package Talus' TPL
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @copyright ©Talus, Talus' Works 2006+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/lgpl.html LGNU Public License 2+
 * @version $Id$
 */

// -- Constantes pour les fichiers
if (!defined('PHP_EXT')) {
  define('PHP_EXT', pathinfo(__FILE__, PATHINFO_EXTENSION));
}

/**
 * Gère le cache des TPLs
 * 
 * @package Talus' TPL
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Talus_TPL_Cache {
  protected 
    $_dir = null,
    $_file = null,
    $_filemtime = 0,
    $_filesize = 0;
  
  private static $_instance = null;
  private function __construct(){}
  private function __clone(){}
  
  /**
   * Pattern Singleton ; si l'instance n'a pas été démarrée, on la démarre...
   * Sinon, on renvoit l'objet déjà créé.
   *
   * @return self
   */
  public static function self(){
    if (self::$_instance === null){
        self::$_instance = new self;
    }
    
    return self::$_instance;
  }
  
  /**
   * @deprecated
   * @ignore
   */
  public static function __init() {
    return self::self();
  }
  
  /**
   * Accessor pour $this->_dir
   *
   * @param string $dir Chemin du cache
   * @return string
   */
  public function dir($dir = null) {
    if ($dir !== null) {
      $dir = rtrim($dir, '/');
      
      if (!is_dir($dir)){
        throw new Talus_TPL_Dir_Exception('Talus_TPL_Cache->dir() :: Le dossier n\'existe pas');
        return false;
      }
      
      $this->_dir = $dir;
    }
    
    return $this->_dir;
  }
  
  /**
   * @deprecated
   * @ignore
   */
  public function getDir(){
    return $this->dir(null);
  }
  
  /**
   * Définit le fichier à stocker
   *
   * @param string $file Nom du fichier à stocker
   * @return void
   */
  public function file($file) {
    $this->_file = trim($file, '.') . '.' . PHP_EXT;
    $file = sprintf('%1$s/%2$s', $this->dir(null), $this->_file);
    
    if (is_file($file)) {
      $this->_filemtime = filemtime($file);
      $this->_filesize = filesize($file);
    } else {
      $this->_filemtime = 0;
      $this->_filesize = 0;
      
      // -- Création du dossier si il n'existe pas
      $dir = dirname($file);
      
      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }
    }
  }
  
  /**
   * @deprecated
   * @ignore
   */
  public function setFile($file) {
    return $this->file($file);
  }
  
  /**
   * Indique si le cache est toujours valide
   *
   * @param integer $time Timestamp de dernière modif du fichier
   * @return boolean
   */
  public function isValid($time) {
    return $this->_filemtime >= abs($time) && $this->_filesize > 0;
  }
  
  /**
   * Ecrit le contenu dans le cache
   *
   * @param string $data Données à écrire
   * @return boolean
   */
  public function put($data) {
    // -- Imposition d'un LOCK maison
    $lockFile = "{$this->dir()}/__tpl_flock__." . sha1($this->_file);
    $lock = @fclose(fopen($lockFile, 'x'));
    
    if (!$lock){
      throw new Talus_TPL_Write_Exception('Talus_TPL_Cache->put() :: Ecriture en cache impossible');
      return false;
    }
    
    file_put_contents("{$this->dir()}/{$this->_file}", $data);
    chmod("{$this->dir()}/{$this->_file}", 0664);
    
    // -- Retirement (suppression) du LOCK
    unlink($lockFile); 
    return true;
  }
  
  /**
   * Execute le contenu du fichier de cache (en l'incluant)
   *
   * @param Talus_TPL $tpl Objet TPL à utiliser lors de la lecture du cache
   * @return bool
   */
  public function exec(Talus_TPL $tpl) {
    if (empty($this->_file)) {
      throw new Talus_TPL_Exec_Exception('Talus_TPL_Cache->exec() :: Impossible d\'executer un fichier "nul"...');
      return false;
    }
    
    extract($tpl->set(null), EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_');
    include sprintf('%1$s/%2$s', $this->dir(), $this->_file);
    $this->_file = null;
    
    return true;
  }
  
  /**
   * Execute le contenu du fichier de cache (en l'incluant)
   * Implémentation de __invoke() pour PHP >= 5.3
   *
   * @param Talus_TPL $tpl Objet TPL à utiliser lors de la lecture du cache
   * @return bool
   *
   * @see self::exec()
   */
  public function __invoke(Talus_TPL $tpl) {
    return $this->exec($tpl);
  }
}

/**
 * EOF
 */
