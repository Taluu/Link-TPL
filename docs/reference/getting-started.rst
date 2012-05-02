Getting started
===============

This is the documentation for Link, an optimized templating engine written in PHP.

It enhances the separation between the logic content and the presentationnal content.

Prerequisites
-------------

Link needs at least **PHP 5.2.1** to run. It also works perfectly fine with 
PHP 5.3 and 5.4.

Installation
------------

You have multiple ways to install Link. If you are unsure what to do, go with
the tarball.

Installing from the tarball release
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Download the most recent tarball from the `download page`_
2. Unpack the tarball
3. Move the files somewhere in your project

Installing the development version
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install Git
2. ``git clone git://github.com/Taluu/Link-TPL.git``

Installing via Composer
~~~~~~~~~~~~~~~~~~~~~~~

1. Install composer in your project:

  .. code-block:: bash

    curl -s http://getcomposer.org/installer | php

2. Create a ``composer.json`` file in your project root:

  .. code-block:: javascript

    {
      "require": {
        "Taluu/Link-TPL": "1.13.*"
      }
    }

3. Install via composer

  .. code-block:: bash

    php composer.phar install

Basic Usage
-----------

This section gives you the basics for getting Link TPL to work.

The first step is (considering you choose to use the Git or Tarball installation
methods) to include the ``Link_Autoloader`` and register it::

  require 'path/to/Link/lib/Autoloader.php';
  Link_Autoloader::register();

Don't forget to replace the ``path/to/Link`` with the proper path you used to 
install Link.

.. note::

  Link follows the `PSR-0 convention`_, which allows you to easily integrate Link
  with other autoloaders (like Composer, Symfony, your-own-autoloader, ...)

.. code-block:: php

  $loader = new Link_Loader_String;
  $cache = new Link_Cache_None;

  $link = new Link_Environnement($loader, $cache);

  // some code logic here...

  $link->parse('Hello {name} !', array('name' => 'Baptiste'));

Link uses a loader (``Link_Loader_String``) to locate templates, a cache engine
(``Link_Cache_None`` which actually does... nothing, disabling the cache, even
though I really would not recommend it -- see below) and an environnement
(``Link_Environnement``) to store all the configuration.

The ``parse()`` method loads the given templates (here a string), checks if it 
is more up to date than the data in the cache ; if it is fresher than the cached
data, it refreshes it, and, with the given context (specified by the second 
argument) renders the template.

.. note::

  As, with a templating engine, we may expect to have our templates stored on 
  files, Link comes bundled with a filesystem loader (``Link_Loader_Filesystem``)
  and a filesystem cache (``Link_Cache_Filesystem``)::

    $loader = new Link_Loader_Filesystem('/path/to/templates');
    $cache = new Link_Cache_Filesystem('/path/to/cached/templates');

    $link = new Link_Environnement($loader, $cache);

    // some code logic here...

    $link->parse('hello.html', array('name' => 'Baptiste'));

That's all folks ! :)

.. _`download page`: https://github.com/Taluu/Link-TPL/tags
.. _`PSR-0 convention`: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md