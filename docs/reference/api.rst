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

Here is an example with the String loader::

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

This is a pretty minimal loader, that implements just the methods given by the
interface. The ``getSource()`` method purpose is to load the content of the
template and to return it ; the ``getCacheKey()``'s goal is to get a cache key ;
and the ``isFresh()`` method is there to check if the template was modified
since a given time or not (it is usually a timestamp). it must return a boolean,
so it should return either ``true`` either ``false``.

Parser
------
todo

Caches
------
todo

Exceptions
----------
todo
