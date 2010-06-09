<?php
/**
 * Liste des filtres pour Talus' TPL.
 * 
 * Si vous désirez rajouter un filtre, ajoutez simplement une méthode 
 * publique statique qui fait les opérations à faire sur la variable,
 * et ce filtre pourra être considéré comme tel par le compilteur de 
 * Talus_TPL (pensez à regénerer votre cache une fois cette opération
 * faite, pour actualiser le code compilé...)
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
 * @package Talus' Works
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @copyright ©Talus, Talus' Works 2008+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @version $Id$
 */

final class Talus_TPL_Filters {
  /**
   * Arrondi la valeur donnée à l'entier supérieur
   *
   * @param string $arg
   * @return string
   */
  public static function ceil($arg){
    return (string) ceil((int) $arg);
  }

  /**
   * Arrondi la valeur à l'entier inférieur
   *
   * @param string $arg
   * @return string
   */
  public static function floor($arg){
    return (string) floor((int) $arg);
  }

  /**
   * Encode les caractères html spéciaux de la variable
   *
   * @param string $arg
   * @return string
   */
  public static function protect($arg){
    return htmlspecialchars($arg);
  }

  /**
   * Met le contenu de la variable en MAJUSCULE
   *
   * @param string $arg
   * @return string
   */
  public static function capitalize($arg){
    return mb_strtoupper($arg);
  }

  /**
   * Met le contenu de la variable en minuscule
   *
   * @param string $arg
   * @return string
   */
  public static function minimize($arg){
    return mb_strtolower($arg);
  }

  /**
   * Met la première lettre d'une variable en Majuscule
   *
   * @param string $arg
   * @return string
   */
  public static function ucfirst($arg){
    $arg[0] = mb_strtoupper($arg[0]);

    return $arg;
  }

  /**
   * Met la première lettre d'une variable en minuscule
   *
   * @param string $arg
   * @return string
   */
  public static function lcfirst($arg){
    $arg[0] = mb_strtolower($arg[0]);

    return $arg;
  }

  /**
   * Met la première lettre de chaques mots d'une variable en Majuscule
   *
   * @param string $arg
   * @return string
   */
  public static function ucwords($arg){
    return mb_convert_case($arg, MB_CASE_TITLE);
  }

  /**
   * Change la casse d'une variable
   *
   * @param string $arg
   * @return string
   */
  public static function invertCase($arg){
    for ($i = 0, $length = mb_strlen($arg); $i < $length; $i++){
      $tolower = mb_strtolower($arg[$i]);
      $arg[$i] = $arg[$i] == $tolower ? mb_strtoupper($arg[$i]) : $tolower;
    }

    return $arg;
  }

  /**
   * Transforme les sauts de lignes en <br />
   *
   * @param string $arg
   * @return string
   */
  public static function nl2br($arg){
    return nl2br($arg);
  }

  /**
   * Créé le slug du nom de l'objet, et le renvoi.
   * Méthode venant du projet Jobeet par le tutoriel Practical Symfony
   *
   * @link http://www.symfony-project.org Framework Symfony
   * @return string Slug de l'argument, n-a si non valide.
   */
  static public function slugify($arg) {
    $arg = trim(preg_replace('`[^\\pL\d]+`u', '-', trim($arg)), '-');

    if (function_exists('iconv')) {
      $arg = iconv('utf-8', 'us-ascii//TRANSLIT', $arg);
    }

    $arg = preg_replace('`[^-\w]+`', '', strtolower($arg));

    if (!$arg) {
      $arg = 'n-a';
    }

    return $arg;
  }

  /**
   * Coupe une chaine de caractères (sans interrompre un mot)
   *
   * @param string $arg chaine à couper
   * @param integer $max nombre maximum de caractères
   * @param string $finish chaine de caractère à appliquer en fin si $str est coupée.
   * @return string
   */
  public static function cut($arg, $max = 50, $finish = '...'){
    if (strlen($arg) <= $max){
      return $arg;
    }

    $max = intval($max) - strlen($finish);

    $arg = substr($arg, 0, $max + 1);
    $arg = strrev(strpbrk(strrev($arg), " \t\n\r\0\x0B"));

    return rtrim($arg) . $finish;
  }

  /**
   * Transforme les sauts de lignes intelligement
   * Deux sauts de lignes font un paragraphe (<p>), et un saut de ligne donne un <br />
   * Adapté de python à php d'après le package "utils.html" de Django.
   *
   * @param string $arg Texte à parser
   * @return string
   */
  public static function paragraphy($arg){
    $arg = str_replace(array("\r\n", "\r"), "\n", $arg);

    $paras = preg_split("`\n{2,}`si", $arg);

    foreach ($paras as &$para) {
      $para = str_replace("\n", '<br />' . "\n", $para);
    }

    return '<p>' . implode('</p>' . PHP_EOL . PHP_EOL . '<p>', $paras) . '</p>';
  }
}

/** EOF /**/
