<?php
/**
 * Gestion du cache FTP de Talus' TPL.
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
 * Gère le cache des TPLs (au niveau du FTP)
 * 
 * @package Talus' TPL
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Talus_TPL_Cache implements Talus_TPL_Cache_Interface {
  protected 
    $_dir = null,
    $_file = array();
  
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
   * Accessor pour $this->_dir
   *
   * @param string $dir Chemin du cache
   * @return string
   */
  public function dir($dir = null) {
    if ($dir !== null) {
      $dir = rtrim($dir, '/');
      
      if (!is_dir($dir)){
        throw new Talus_TPL_Dir_Exception(array('Dossier "%s" non existant.', $dir));
        return false;
      }
      
      $this->_dir = $dir;
    } elseif ($this->_dir === null) {
      $this->_dir = sys_get_temp_dir();
    }
    
    return $this->_dir;
  }
  
  /**
   * Définit le fichier à stocker
   *
   * @param string $file Nom du fichier à stocker
   * @return array Informations sur le fichier en cache
   */
  public function file($file = null) {
    if ($file !== null) {
      $file = sprintf('%1$s/tpl_%2$s.%3$s', $this->dir(null), sha1(trim($file, '.')), PHP_EXT);

      $filemtime = 0;
      $filesize = 0;

      if (is_file($file)) {
        $filemtime = filemtime($file);
        $filesize = filesize($file);
      }

      $this->_file = array(
        'url' => $file,
        'last_modif' => $filemtime,
        'size' => $filesize
       );
    }

    return $this->_file;
  }
  
  /**
   * Indique si le cache est toujours valide
   *
   * @param integer $time Timestamp de dernière modif du fichier
   * @return boolean Vrai si le cache est encore valide, faux sinon.
   */
  public function isValid($time) {
    $file = $this->file(null);
    return $file['last_modif'] >= abs($time) && $file['size'] > 0;
  }
  
  /**
   * Ecrit le contenu dans le cache
   *
   * @param string $data Données à écrire
   * @return boolean
   */
  public function put($data) {
    // -- Récupération des informations du fichier cache
    $file = $this->file(null);

    // -- Imposition d'un LOCK maison
    $lockFile = sprintf('%1$s/__tpl_flock__.%2$s', $this->dir(null), sha1($file['url']));
    $lock = @fclose(fopen($lockFile, 'x'));

    if (!$lock){
      throw new Talus_TPL_Write_Exception('Ecriture en cache impossible');
      return false;
    }

    file_put_contents($file['url'], $data);
    chmod($file['url'], 0664);

    // -- Retirement (suppression) du LOCK
    unlink($lockFile);
    return true;
  }

  /**
   * Supprime le fichier cache.. Si il existe.
   *
   * @return void
   */
  public function destroy() {
    $file = $this->file(null);

    if ($file !== array()) {
      unlink($file['url']);
    }
  }
  
  /**
   * Execute le contenu du fichier de cache (en l'incluant)
   *
   * @param Talus_TPL $tpl Objet TPL à utiliser lors de la lecture du cache
   * @return bool status de l'execution
   */
  public function exec(Talus_TPL $tpl) {
    $file = $this->file(null);

    if ($file === array()) {
      throw new Talus_TPL_Exec_Exception('Vous venez d\'essayer d\executer aucun fichier...');
      return false;
    }

    extract($tpl->set(null), EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_');
    include $file['url'];

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
