Link TPL
==========
A templating engine in PHP, using a XML syntax like, and a parser based on PCREs.

**Requires** at least *PHP 5.2.1*, because of the utilisation of the Standard PHP
Library and several object specifications available only since 5.2.1. Compatible
PHP 5.3 and 5.4.

[![Build Status](https://secure.travis-ci.org/Taluu/Link-TPL.png?branch=master)](http://travis-ci.org/Taluu/Link-TPL)

Documentation
-------------
For any pieces of documentation, check the 
[official site](http://www.talus-works.net)
([Talus' TPL > Documentation](http://www.talus-works.net/forum-6-p1-rapports-de-bugs.html)).
It is still in french though.

A recent version of the documentation (in english) is being written in the `docs`
folder, but is not complete for now.

For Link < 1.13, you may find a documentation on 
[Pingax](http://github.com/Pingax)'s fork of 
[Link TPL](http://github.com/Pingax/Link-TPL/).

Installation
------------

You have multiple ways to install Link. If you are unsure what to do, go with
the tarball.

1. From the tarball release

  1. Download the most recent tarball from the [download page](https://github.com/Taluu/Link-TPL/tags)
  2. Unpack the tarball
  3. Move the files somewhere in your project

2. Development version

  1. Install Git
  2. `git clone git://github.com/Taluu/Link-TPL.git`

3. Via Composer

  1. Install composer in your project: `curl -s http://getcomposer.org/installer | php`
  2. Create a `composer.json` file in your project root:

    ```javascript

    {
      "require": {
        "Taluu/Link-TPL": "1.13.*"
      }
    }

    ```

  3. Install via composer : `php composer.phar install`

About this project...
---------------------
This project was initiated as a fork "from scratch" from the templating engine
of the [Fire Soft Board](http://www.fire-soft-board.com) project. It also has 
some stuff inspired from the [Twig](https://github.com/fabpot/Twig)'s, 
[PHPBB3](https://github.com/phpbb/phpbb3)'s and 
[Django](https://github.com/django/django)'s templating engines.
