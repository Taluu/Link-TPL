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
todo

Parser
------
todo

Caches
------
todo

Exceptions
----------
todo
