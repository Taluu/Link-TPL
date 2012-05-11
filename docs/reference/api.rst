Link TPL for developpers
========================
This chapter will mainly interest those who are aiming to use Link as a
**developper**. Here, you will learn how and what is the purpose of some
elements of the Link Template Library.

The Environment
---------------
As you saw in the :doc:`getting-started` chapter, to start, you will have to use
a ``Link_Environment`` object to use Link Templates. Basically, here is what you 
will need (if you are using a PSR-0 compatible autoloader, you may skip the part
on the autoloader) ::

  require 'path/to/Link/lib/Link/Autoloader.php';
  Link_Autoloader::register();

  $link = new Link_Environment;

This class is used to store all the configuration and datas you will be using in
your templates, and will be useful for some template actions, but these won't be
mentionned in this chapter. In this object, you may store whatever cache engine
and loader you want to use ; per default, it is the "no-cache" cache engine and
the "string" loader that are used. To alter them, you have several way to
achieve that :

- In the constructor of the ``Link_Environment``, you may tell the engine which
  instance to use for the loader and the cache::

    $link = new Link_Environment(new My\Fancy\Loader, new My\Fancy\Cache);

- You can also use a setter given by ``Link_Environment``::

    $link->setLoader(new My\Fancy\Loader);
    $link->setCache(new My\Fancy\Cache);

As the third argument of ``Link_Environment`` construtor, you may pass an array
of options to the environment. Here are a list of the built-in options provided :

- *dependencies* : You may specify which dependency to use when working with
  Link, like changing the Parser. This is the sole dependency changement
  possible for now.

- *force_reload* : This is a boolean, telling the environment if you wish to use
  the cache or always recompile the template. It is deactivated by default. It
  can be useful in a dev environment, without changing the cache engine each
  time you need to do it.

Loaders
-------
When you are using Link, you have to load datas to work on them and then print
out the result. The most common loader is the Filesystem loader (loading a
template from the filesystem), or the String loader (loading a template from a
string).

Built-in loaders
^^^^^^^^^^^^^^^^
You have two built-in loaders given with a basic download of Link : the String
loader (which is the default) and the Filesystem::

  $string = new Link_Loader_String;
  $filesystem = new Link_Loader_Filesystem($templateDir);

  // When loading a template...
  $filesystem->getSource('my/template.httml.link');
  $string->getSource('a string with a {variable}');

The Filesystem loader searches the template in the ``$templateDir`` directory,
and if it doesn't find the template, it will throw an error. If you provide
an array of directory, like this::

  $filesystem = new Link_Loader_Filesystem(array($templateDir, $templateDir2));

Then the template will be searched in ``$templateDir``, and if it is not found
in this directory, will try to load it from ``$templateDir2``.

.. warning::
  As you should expect it, if you're using several directories via an array,
  the search of a template will use the "First Come First Served" basic rule !
    
Build your own loader
^^^^^^^^^^^^^^^^^^^^^
You may also develop your own loader (example : you want to load some data from
a database), and use it if you implement the ``Link_Interface_Loader`` interface,
the interface that all the built-in loaders implements::

  interface Link_Interface_Loader {
    /**
    * Checks whether the object is fresher or not than the `$_time`
    *
    * @param string $_name Object's name / value
    * @param integer $_time Last modification's timestamp
    * @return boolean true if fresher, false if not
    */
    public function isFresh($_name, $_time);

    /**
    * Gets the content of the object
    *
    * @param string $_name Name of the content to be retrieved
    * @return string object's content
    */
    public function getSource($_name);

    /**
    * Gets the cache key associated with the object
    *
    * @param string $_name Name designing the object
    * @return string Cache key to be used, hashed by sha1
    */
    public function getCacheKey($_name);
  }

An example with the String loader will be more explicit::

  class Link_Loader_String implements Link_Interface_Loader {
    public function getCacheKey($_name) {
      return sha1($_name);
    }

    public function getSource($_name) {
      return $_name;
    }

    public function isFresh($_name, $_time) {
      return true;
    }
  }

Parser
------
To transform a Link syntax into a valid and optimized PHP code, Link have to
parse the data to make it executable by PHP. We are going to base this
documentation on the built-in parser given in Link, its options, and how to
build your own parser.

Available options
^^^^^^^^^^^^^^^^^
When you're using ``Link_Parser``, which is the sole built-in parser offered by
Link, you may suggest some options to alter its behaviour. You may have two ways
for modifying a parameter :

- You pass it to the constructor, with the ``$options`` array, which is the sole
  argument asked by the constructor (at least for the built-in parser)

- You use an appropriate getter and setter.

So here is the list of available options (per default) :

- First, the things to parse (``parse`` key). You may choose, with a bitmask, 
  what you want to effectively parse : constants, conditions, filters, ... But
  you may not deactivate the "core" features like the variables and loops.

  ========== ===============================================================
  Flag Name  Flag Description
  ========== ===============================================================
  FILTERS    Transforms the filters
  INCLUDES   Transforms the `<include>` tags
  CONDITIONS Transforms the `<if>` tags
  CONSTANTS  Transforms the constants
  ---------- ---------------------------------------------------------------
  BASICS     Basics suggested : Transforms at least the conditions
  DEFAULTS   Defaults suggested : Transforms everything. This is the default
  ALL        Transforms everything (bitmask containing everything
  ========== ===============================================================

- The output given, with the ``compact`` option. If it is true, then the code
  will be compressed, meaning that not only the ``?><?php`` tags will be removed,
  but also any ``?><?php`` with blancs between them willl be cleansed. If you're
  using at least PHP 5.4, the ``<?php echo`` will be transformed into ``<?=``.
  If it is false, then only ``?><?php`` will be cleansed.

- You may also changed the filters class you want to use via the ``filters``
  option. This option expects a class name.

Build your own parser
^^^^^^^^^^^^^^^^^^^^^
You may also build your own parser ; you just need to implements the 
``Link_Interface_Parser`` class::

  interface Link_Interface_Parser {
    /**
     * Getter for a given parameter
     *
     * @param string $name Parameter's name
     * @return mixed Parameter's value
     */
    public function getParameter($name);
    
    /**
     * Setter for a given parameter
     *
     * @param string $name Parameter's name
     * @param mixed $val Parameter's value
     * @return mixed Parameter's value
     */
    public function setParameter($name, $val = null);

    /**
     * Checks whether or not this class has the `$name` parameter
     *
     * @param string $name Parameter's name
     * @return bool true if this parameter exists, false otherwise
     */
    public function hasParameter($name);

    /**
     * Transform a TPL syntax towards an optimized PHP syntax
     *
     * @param string $str TPL script to parse
     * @return string
     */
    public function parse($str);
  }

You may change the default parser by specifying it in the constructor of the
environment ``Link_Environment``.

Cache Managers
--------------
Using Link may ask for some performances when parsing templates. To avoid to
parse somthing that is unchanged since the last time it was parsed, we may have
to use a Cache, that is responsible to ask for a refresh of the result.

Built-in cache managers
^^^^^^^^^^^^^^^^^^^^^^^
You have two built-in cache managers given with a basic download of Link : the 
Ghost (which is the default) and the Filesystem::

  $none = new Link_Cache_None;
  $filesystem = new Link_Cache_Filesystem($cacheDir);

If you give no argument to the Filesystem Cache Manager, it will try to use the
default temp directory of your system via the ``sys_get_temp_dir()`` php 
function.
    
Build your own cache manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^
You may also develop your own cache manager (example : you want to save data in
a database), and use it if you implement the ``Link_Interface_Cache`` interface,
the interface that all the built-in cache managers implements::

  interface Link_Interface_Cache {
    /**
    * Gets the last modified time for the selected key
    *
    * @param string $_key key designing the cache
    * @return integer last modification unix timestamp of the file
    */
    public function getTimestamp($_key);

    /**
    * Write the content in the cache file
    *
    * @param string $_key key designing the cache
    * @param string $data Data to be written
    * @return boolean
    */
    public function put($_key, $_data);

    /**
    * Delete the current cache id.
    *
    * @param string $_key key designing the cache
    * @return void
    */
    public function destroy($_key);

    /**
    * Fetches & executes the cache content
    *
    * @param string $_key key designing the cache
    * @param Link_Environment $_env TPL environnement to be given to the template
    * @param array $_context Local variables to the template
    */
    public function exec($_key, Link_Environment $_env, array $_context = array());
  }

An example with the Ghost cache should be more explicit than any explications::

  class Link_Cache_None implements Link_Interface_Cache {
    protected $_datas = array();

    public function destroy($_key) {
      return; // no reason to do anything, is there ? :o
    }

    public function getTimestamp($_key) {
      return 0; // the template is always fresher than the cache
    }

    public function put($_key, $_data) {
      $this->_datas[$_key] = $_data; // Stocking the compilation result only...
    }

    public function exec($_key, Link_Environment $_env, array $_context = array()) {
      if (!isset($this->_datas[$_key])) {
        throw new Link_Exception_Cache('No data sent.');
      }

      if (extract($_context, EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_') < count($_context)) {
        trigger_error('Some variables couldn\'t be extracted...', E_USER_NOTICE);
      }

      // -- GAWD I don't like this method :(
      eval('?>' . $this->_datas[$_key] . '<?php');
    }

    /**
    * Executes the file's content
    * Implementation of the magic method __invoke() for PHP >= 5.3
    *
    * @param string $_key Key representating the cache file
    * @param Link_Environment $tpl TPL environnement to be used during cache reading
    * @param array $_context Variables to be given to the template
    * @return bool
    *
    * @see self::exec()
    */
    public function __invoke($_key, Link_Environment $_env, array $_context = array()) {
      return $this->exec($_key, $_env, $_context);
    }
  }


Exceptions
----------
When Link encounters an error, it may throw some exceptions. All the exceptions
are inherited from ``Link_Exception``, and are all just shell to help identify
the error or the exception. Here is the list of the built-in exceptions that
Link may throw :

- ``Link_Exception_Cache`` : Thrown when an error in the cache treatment occurs

- ``Link_Exception_Loader`` : Thrown when an error while trying to load a 
  template occurs

- ``Link_Exception_Parser`` : Thrown when an error while trying to parse a 
  template occurs

- ``Link_Exception_Runtime`` : Thrown when an error while trying to execute a 
  parsed template occurs (like when there's an error when trying to include a
  template).
