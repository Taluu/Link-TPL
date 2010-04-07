<?php
/**
 * Interface pour le cache des templates.
 * (Facilite la Dependency Injection)
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
 * @link http://www.slideshare.net/fabpot/dependency-injection-with-php-53 Slideshare DI
 * @license http://www.gnu.org/licenses/lgpl.html LGNU Public License 3+
 * @version $Id$
 */

interface Talus_TPL_Cache_Interface extends Talus_TPL_Dependency_Interface {
  /**
   * Définit le répertoire, ou plutôt l'emplacement des fichiers caches
   * (dossier si ftp, base de données si cache sql, ...)
   *
   * Si $dir est à null, le dossier courant est renvoyé.
   *
   * @param string|null $dir Dossier à définir
   */
  public function dir($dir = null);

  /**
   * Définit le tpl à mettre en cache (ou plutôt l'id du tpl en compilation).
   * Agit aussi comme getter si $file est à null
   *
   * @param string|null $file Fichier concerné
   */
  public function file($file = null);

  /**
   * Indique si le cache est expiré pour un certain template (et si il faut donc
   * le recompiler)
   *
   * @param integer $time Date à comparer
   */
  public function isValid($time);

  /**
   * Insère le résultat d'une compilation (ou plutôt de la variable $data) dans
   * le cache correspondant
   *
   * @param string $data Code PHP à insérer
   */
  public function put($data);

  /**
   * Détruit le cache donné pour le tpl actuel.
   */
  public function destroy();

  /**
   * Execute le contenu du cache
   * 
   * @param Talus_TPL $tpl TPL à executer
   */
  public function exec(Talus_TPL $tpl);
}

/*
 * EOF
 */
