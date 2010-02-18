<?php
/**
 * Compilateur de Talus' TPL.
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
 
// -- Si PHP < 5.3, déclaration de E_USER_DEPRECATED et de la classe Closure
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
  define('E_USER_DEPRECATED', E_USER_NOTICE);
  //final class Closure {}
}

/**
 * "Compilateur" (ou plutôt Interpréteur) de templates
 *
 * Cette classe gère la transformation d'un code Talus TPL en un code PHP
 * optimisé et interprétable par PHP.
 */
class Talus_TPL_Compiler {
  protected $_parameters = array();
    
  const
    SET = 1,
    FILTERS = 2,
    INCLUDES = 4,
    FUNCTIONS = 8,
    FOREACHS = 16,
    CONDITIONS = 32,
    CONSTANTS = 64,
    
    BASICS = 36,
    DEFAULTS = 119,
    ALL = 127;
  
  private static $_inst = null;
  private function __clone(){}
  
  /**
   * @ignore
   */
  private function __construct(){
    $this->parameter('parse', self::DEFAULTS);
    $this->parameter('set_compact', false);
    $this->parameter('namespace', '');
  }
  
  /**
   * Pattern Singleton ; si l'instance n'a pas été démarrée, on la démarre...
   * Sinon, on renvoit l'objet déjà créé.
   *
   * @return Talus_TPL_Compiler
   */
  public static function self(){
    if (self::$_inst === null){
      self::$_inst = new self;
    }
    
    return self::$_inst;
  }
  
  /**
   * @deprecated
   * @ignore
   */
  public static function __init() {
    return self::self();
  }
  
  /**
   * @deprecated
   * @ignore
   */
  public function getNamespace() {
    return $this->parameter('namespace');
  }
  
  /**
   * @deprecated
   * @ignore
   */
  public function setNamespace($namespace = 'tpl') {
    $this->parameter('namespace', $namespace);
  }
  
  /**
   * Réglage / Récupération de la valeur d'un paramètre
   *
   * @param string $param Nom du paramètre
   * @param mixed $value Valeur du paramètre
   * @return mixed Valeur du paramètre
   */
  public function parameter($param, $value = null) {
    if ($value !== null) {
      $this->_parameters[$param] = $value;
    }
    
    return $this->_parameters[$param];
  }
  
  /**
   * Transforme une chaine en syntaxe TPL vers une syntaxe PHP.
   * 
   * @param string $compile Code TPL à compiler
   * @return string
   */
  public function compile($compile){
    $compile = str_replace('<?' ,'<?php echo \'<?\'; ?>', $compile);
    $compile = preg_replace('`/\*.*?\*/`s', '', $compile);
    
    $nspace = $this->parameter('namespace');

    if (!empty($nspace)) {
      $nspace .= ':';
    }
    
    // -- Utilisation de filtres (parsage récursif)
    if ($this->parameter('parse') & self::FILTERS) {
      $matches = array();
      while (preg_match('`\{(?:(KEY|VALUE|GLOB),)?(\$?[a-zA-Z_\xc0-\xd6\xd8-\xde][a-zA-Z0-9_\xc0-\xd6\xd8-\xde.]*(?:\[(?!]\|)(?:.*?)])?)\|((?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\|?)+)}`', $compile, $matches)) {
        $compile = str_replace($matches[0], $this->_filters($matches[2], $matches[3], $matches[1]), $compile);
      }
    }
    
    // -- Appels de fonctions (Déprécié)
    if ($this->parameter('parse') & self::FUNCTIONS) {
      $compile = preg_replace_callback('`<' . $nspace . 'call ' . $nspace . 'name="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"((?: ' . $nspace . 'arg="[^"]*?")*) />`', array($this, '_callFunction'), $compile);
    }
    
    // -- Les blocs
    $compile = preg_replace_callback('`<' . $nspace . 'block ' . $nspace . 'name="([a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*)"(?: ' . $nspace . 'parent="([a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*)")?>`', array($this, '_block'), $compile);
    $compile = preg_replace_callback('`<' . $nspace . 'block ' . $nspace . 'name="([a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*)\.([a-z0-9_\xe0-\xf6\xf8-\xff]+)">`', array($this, '_block__Old'), $compile);

    // -- Inclusions
    if ($this->parameter('parse') & self::INCLUDES) {
      $compile = preg_replace_callback('`<' . $nspace . '(include|require) ' . $nspace . 'tpl="((?:.+?\.html(?:\?[^\"]*)?)|(?:\{\$(?:[a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*\.)?[A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*(?:\[(?!]})(?:.*?)])?}))"(?: ' . $nspace . 'once="(true|false)")? />`', array($this, '_includes'), $compile);
    }

    // -- Regex non complexes qui n'ont pas besoin d'un traitement récursif
    $not_recursives = array();
    
    // -- Balises ne nécessitant pas de Regex
    $noRegex = array(
      "</{$nspace}block>" => '<?php } unset($__tplBlock[array_pop($__tpl_block_stack)]); endif; ?>', 
      "<{$nspace}blockelse />" => '<?php } else : if (true) { ?>', 
      
      '{\\' =>  '{'
     );
    
    // -- Regex non complexes qui ont besoin d'un traitement récursif
    $recursives = array(
      // -- Variables simples
      '`\{([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*(?:\[(?!]})(?:.*?)])?)}`' => '<?php echo $__tpl_vars__$1; ?>',
      '`\{\$([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*(?:\[(?!]})(?:.*?)])?)}`' => '$__tpl_vars__$1',
      
      // -- Variables Blocs
      '`\{([a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*)\.([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)(\[(?!]})(?:.*?)])?}`' => '<?php echo $__tplBlock[\'$1\'][\'$2\']$3; ?>',
      '`\{\$([a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*)\.([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)(\[(?!]})(?:.*?)])?}`' => '$__tplBlock[\'$1\'][\'$2\']$3'
     );
    
    // -- Balise <set>
    if ($this->parameter('parse') & self::SET) {
      $not_recursives['`<' . $nspace . 'set ' . $nspace . 'var="([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)(\[(?!]">)(?:.*?)])?">(?!"</set>)(.+?)</set>`'] = '<?php $__tpl_vars__$1$2 = \'$3\'; ?>';
    }
    
    // -- Constantes
    if ($this->parameter('parse') & self::CONSTANTS) {
      $not_recursives['`\{__([a-zA-Z_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde][a-zA-Z0-9_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde]*)__}`i'] = '<?php echo $1; ?>';
    }
    
    // -- Balises de Conditions (<if>, <elseif>, <else>)
    if ($this->parameter('parse') & self::CONDITIONS) {
      $not_recursives = array_merge($not_recursives, array(
        '`<' . $nspace . 'if ' . $nspace . 'cond(?:ition)?="(?!">)(.+?)">`' => '<?php if ($1) : ?>',
        '`<' . $nspace . 'elseif ' . $nspace . 'cond(?:ition)?="(?!" />)(.+?)" />`' => '<?php elseif ($1) : ?>'
       ));
       
      $noRegex["<{$nspace}else />"] = '<?php else : ?>';
      $noRegex["</{$nspace}if>"] = '<?php endif; ?>';
    }
    
    // -- Balises <foreach>
    if ($this->parameter('parse') & self::FOREACHS) {
      $not_recursives = array_merge($not_recursives, array(      
        // -- Foreachs
        '`<' . $nspace . 'foreach ' . $nspace . 'ar(?:ra)?y="\{\$([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)}">`i' => '<?php foreach ({$$1} as $__tpl_foreach_key[\'$1\'] => &$__tpl_foreach_value[\'$1\']) : ?>',
        '`<' . $nspace . 'foreach ' . $nspace . 'ar(?:ra)?y="\{((?:(?:VALUE,)?\$[A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)(?:\[(?!]})(?:.*?)])?)}" ' . $nspace . 'as="\{\$([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}">`i' => '<?php foreach ({$1} as $__tpl_foreach_key[\'$2\'] => &$__tpl_foreach_value[\'$2\']) : ?>', 
        
        // -- Clés Foreach
        '`\{KEY,([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)}`i' => '<?php echo $__tpl_foreach_key[\'$1\']; ?>',
        '`\{KEY,\$([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)}`i' => '$__tpl_foreach_key[\'$1\']'
       ));
       
      $recursives = array_merge($recursives, array(
        // -- Valeurs Foreachs
        '`\{VALUE,([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)(\[(?!]})(?:.*?)])?}`' => '<?php echo $__tpl_foreach_value[\'$1\']$2; ?>',
        '`\{VALUE,\$([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)(\[(?!]})(?:.*?)])?}`' => '$__tpl_foreach_value[\'$1\']$2'
       ));
       
       $noRegex["</{$nspace}foreach>"] = '<?php endforeach; ?>';
    }
    
    $compile = preg_replace(array_keys($not_recursives), array_values($not_recursives), $compile);
    
    foreach ($recursives as $regex => $replace) {
      while(preg_match($regex, $compile)) {
        $compile = preg_replace($regex, $replace, $compile);
      }
    }

    // -- Les définitions de fonctions (Déprécié)
    if ($this->parameter('parse') & self::FUNCTIONS) {
      $compile = preg_replace_callback('`<' . $nspace . 'function ' . $nspace . 'name="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"((?: ' . $nspace . 'arg="[A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*")*)>(.+?)</function>`s', array($this, '_defineFunction'), $compile);
    }
    
    // -- Les str_replace (moins de ressources que les preg_replace !)
    $compile = str_replace(array_keys($noRegex), array_values($noRegex), $compile);

    /* 
     * On nettoie le code... De manière étendue ou simple suivant le paramètre
     * "set_compact". Si il est activé, alors tout ce qui est considéré comme
     * "vide" entre deux balises PHP (?><?php) est supprimé, les balises inclues.
     *
     * Sinon, un simple str_replace de deux balises PHP est effectué.
     */
    if ($this->parameter('set_compact')) {
      $compile = preg_replace('`\?>/s*<\?php`', '', $compile);
    } else {
      $compile = str_replace('?><?php', '', $compile);
    }

    return $compile;
  }
  
  /**
   * Compile un code TPL donné
   * Implémentation de __invoke() pour PHP >= 5.3
   *
   * @param string $compile Code TPL à compiler
   * @return string
   * @see self::compile()
   */
  public function __invoke($compile) {
    return $this->compile($compile);
  }
    
  /**
   * Parse les blocs pour la syntaxe de Talus_TPL >= 1.6.0
   *
   * @param array $match Capture de la regex
   * @return string
   * @see self::compile()
   * @see 97
   */
  protected function _block(array $match){
    /*
     * Par défaut (absence de blocs parents), on a juste à récupérer le bloc
     * à la racine... Ca sert à la fois de condition, et à la fois pour le nom
     * du bloc.
     * 
     * Sinon, il faut récupérer le bloc parent, désigner pour le "bloc"
     * l'itération actuelle du parent, et pour la condition, verifier que le
     * bloc existe.
     */
    $cond = $block = sprintf('$tpl->block(\'%s\', null)', $match[1]);

    if (!empty($match[2])) {
      $block = sprintf('$__tplBlock[\'%2$s\'][\'%1$s\']', $match[1], $match[2]);
      $cond = sprintf('isset(%s)', $block);
    }
    
    // -- Variable référençant le bloc actuel
    $ref = '$__tpl_' . sha1(uniqid(mt_rand(), true));
    
    /* 
     * Afin de pouvoir faire un foreach par référence, celui-ci demandant
     * forcément une variable (et pas une fonction) pour pouvoir faire une
     * itération par référence, on est ainsi obligé de créer un bloc
     * temporaire pour récupérer la référence retournée par $tpl->getBlock...
     *
     * Aussi, pour éviter un éventuel conflit de référenes (bloc appelé deux
     * fois par exemple), qui est un bug connu de PHP, on instaure une sorte de
     * "pile de noms de blocs" en cours, qu'on bidouille pour supprimer le
     * symbole référence créée par le foreach (phew).
     */
    return sprintf('<?php if (%1$s) : %2$s = &%3$s; $__tpl_block_stack[] = \'%4$s\'; foreach (%2$s as &$__tplBlock[\'%4$s\']){ ?>',
                   $cond, $ref, $block, $match[1]);
  }
    
  /**
   * @ignore
   * @deprecated
   */
  protected function _block__Old(array $match){
    // -- Notice pour deprecated
    trigger_error('La syntaxe <block name="parent.enfant"> est dépréciée ;
                   Veuillez mettre à jour votre script TPL pour <block name="enfant" parent="parent">',
                   E_USER_DEPRECATED);
    
    // -- Appel de la méthode actuelle, et inversion de match[1] et match[2]
    $this->_Block(array($match[0], $match[2], $match[1]));
  }
  
  /**
   * Parse les déclarations de fonctions
   *
   * @param array $matches Capture de la regex
   * @return string syntaxe de définition de la fonction
   * @see self::_compile()
   * @see #159
   *
   * @deprecated
   */
  protected function _defineFunction(array $matches){
    // -- Notice pour deprecated
    trigger_error('Les fonctions TPL sont maintenant dépréciées ; Veuillez mettre à jour votre script TPL pour les inclusions paramétrées.', E_USER_DEPRECATED);

    $nspace = $this->parameter('namespace');
    $php = sprintf('<?php function __tpl_%s(Talus_TPL $tpl, ', $matches[1]);

    if (!empty($nspace)) {
      $nspace .= ':';
    }
    
    // -- Demande d'arguments...
    if (!empty($matches[2])) {
      $matches[2] = ltrim(mb_substr($matches[2], 5 + mb_strlen($nspace), -1));
      $args = explode(sprintf('" %sarg=" ', $nspace), $matches[2]);

      foreach ($args as &$arg) {
        $php .= sprintf('$%s, ', $arg);
      }
    }
    
    $php = rtrim($php, ', ') . '){ $__tpl_vars = $tpl->set(null); ?>';
    
    $script = preg_replace('`\$__tpl_vars__([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)`', '$$1', $matches[3]);
    $script = preg_replace('`\$GLOB,([A-Z_\xc0-\xd6\xd8-\xde][A-Z0-9_\xc0-\xd6\xd8-\xde]*)`', '$__tpl_vars[\'$1\']', $script );
    
    return sprintf('%1$s%2$s<?php } ?>', $php, $script);
  }
  
  /**
   * Parse les appels de fonctions
   *
   * @param array $matches Capture de la regex
   * @return string syntaxe à utiliser pour appeler la fonction
   * @see self::_compile()
   * @see #249
   * 
   * @deprecated
   */
  protected function _callFunction(array $matches){
    // -- Notice pour deprecated
    trigger_error('Les fonctions TPL sont maintenant dépréciées ; Veuillez
                   mettre à jour votre script TPL pour les inclusions paramétrées.', E_USER_DEPRECATED);
    
    $nspace = $this->parameter('namespace');
    $php = sprintf('<?php __tpl_%s($tpl, ', $matches[1]);

    if (!empty($nspace)) {
      $nspace .= ':';
    }
    
    if (!empty($matches[2])) {
      $args = explode(sprintf('" %sarg="', $nspace), mb_substr(ltrim($matches[2]), 5 + mb_strlen($nspace), -1));
      
      foreach ($args as &$arg ){
        $php .= $this->_escape($arg) . ', ';
      }
    }
    
    return rtrim($php, ', ') . '); ?>';
  }
  
  /**
   * Parse les filtres $filters pour une variable $var donnée
   *
   * @param mixed $var Variable à parser
   * @param string $filters Filtres à parser
   * @param string $type Type de la variable (pour {TYPE,VAR})
   * @return string variable filtrée
   */
  protected function _filters($var = '', $filters = '', $type = null){
    $brackets = 0;
    $return = '';
    $toPrint = false;
    $var = sprintf('{%s}', $var);
    $filters = array_reverse(array_filter(explode('|', $filters)));
    
    /*
     * Si on souhaite afficher la variable (absence du $ significatif), il
     * faut alors bidouiller la variable pour qu'elle ait un $... En étant
     * affichée, et non retournée.
     *
     * Si c'est le cas, on a juste besoin de rajouter le $ devant le nom
     * de la variable...
     */
    if ($var[1] != '$') {
      $var = '{$' . mb_substr($var, 1);
      $toPrint = true;
    }
    
    /*
     * Si on a affaire à une variable du type {TYPE,VAR}, on doit alors
     * remplacer la première accolade ouvrante "{" (caractéristique des
     * variables TPL) par "{TYPE,"
     */
    if (!empty($type)) {
      $var = sprintf('{%1$s,%2$s', $type, mb_substr($var, 1));
    }
    
    foreach ($filters as &$filter) {
      // -- Filtre non déclaré ?
      if (!method_exists('Talus_TPL_Filters', $filter)){
        trigger_error("Le filtre \"$filter\" n'existe pas, et sera donc ignoré.\n\n",
                      E_USER_NOTICE);
        continue;
      }
      
      // -- Ajout du filtre, incrémentation du nombre de (
      $return .= sprintf('Talus_TPL_Filters::%s(', $filter);
      ++$brackets;
    }
    
    // -- Association de la variable, fermeture des différentes ( ouvertes
    $return .= $var . str_repeat(')', $brackets);
    
    /*
     * Si la variable ne commence pas par un $, il faut alors afficher son
     * contenu.
     */
    if ($toPrint === true){
      $return = sprintf('<?php echo %s; ?>', $return);
    }
    
    return $return;
  }
  
  /**
   * Parse les inclusions
   *
   * @param array $match Capture de la Regex
   * @return string fonction d'inclusion tpl
   */
  protected function _includes(array $match) {
    $qs = '';

    // -- Présence d'un Query String
    // TODO : Trouver un meilleur moyen pour les vars...
    if (strpos($match[2], '?') !== false) {
      list($match[2], $qs) = explode('?', $match[2], 2);
      $qs = sprintf(' . "?%s"', str_replace(array('{', '}'), array('{{', '}}'), $qs));
    }

    return sprintf('<?php $tpl->includeTpl(%1$s%2$s, %3$s, Talus_TPL::%4$s_TPL); ?>',
                   $this->_escape($match[2]), $qs,
                   isset($match[3]) && $match[3] == 'true' ? 'true' : 'false',
                   mb_strtoupper($match[1]));
  }
  
  /**
   * Echappe une valeur, suivant que ce soit une chaine de caractères, une
   * variable, ou des nombres.
   *
   * @param string $arg Valeur à échapper
   * @param string $delim Délimiteur de la chaine
   * @return string Valeur échappée
   */
  protected function _escape($arg, $delim = '\'') {
    if (($arg[0] != '{' || $arg[mb_strlen($arg) - 1] != '}') && !ctype_digit($arg)) {
      $arg = sprintf('%1$s%2$s%1$s', $delim, addcslashes($arg, $delim));
    }
    
    return $arg;
  }
}

/** EOF /**/
