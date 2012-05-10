Link TPL for designers
======================
This chapter presents the syntax used in the templates and will mainly be useful
for the guys in charge of building the templates.

.. _rapid-overview:

Rapid Overview
--------------
A template file is just a text file that could be rendered into *any text-format* 
(be it HTML, XML, simple text file, JSON, ...). It does not have a particular 
extension ; usually, we use the type of document it is destined to be (like 
``.html`` if it is a html document, ``.json`` if it is json, ...).

.. note::
  Usually, when working with templates, we suffix the template engine's name at
  the end of the final format of the file. For example, for a HTML file called
  ``template.html``, we should name it ``template.html.link`` if we're using
  Link as the template engine to parse it. If it is destined to be a RSS file, 
  then it should be ``template.rss.link``.

It is mainly constitued by **variables** and **tags**, which are xml tags 
look-alike. Here is a sample Link template (the explications on the syntax will 
come afterwards) :

.. code-block:: xml

  <!doctype HTML>
  <html>
    <head>
      <title>Hello {name} !</title>
    </head>
    <body>
      <ul id="menu">
        <foreach array="{$menu}">
          <li><a href="{menu.value['link']}">{menu.value['label']}</a></li>
        </foreach>
      </ul>

      <p>Hello {name} !</p>
    </body>
  </html>

As you may see, there are typically two types of instructions : ``{var}``,
representing a variable and printing it, and several xml-like tags like
``<foreach>``, ``<if>``, ... containing the logic of the template. Of course,
there are other types of instructions, but these two are the main ones you
should keep in mind when creating Link templates.

Comments
--------
You can comment some part of code in the template : it will not be rendered in
the result, and are just there to help the designer. The main purpose is to give
a message to other template designers or to yourself, or to debug something. The
syntax for this is ``{* ... *}``

.. code-block:: xml

  {* useless ?
    <if condition="true === true"></if>
  *}

Variables
---------
The application may pass variables to the templates, so that you can do almost
whatever you want in your templates : conditionning, looping, printing,
filtering, ...

As it was said in the :ref:`rapid-overview`, there are two types of variables,
depending of the role you want them to be : either you will want to print them
out, or you will just want to manipulate the variable by itself. 

To print it out, you have to use the following (for let's say a ``var``
variable)::

  {var}

And to use the variable as an entity, you will need to use the following::

  {$var}

.. note::
  As in PHP, if your variable is an object, you may want to access its properties,
  or if your variable is an array its components. For that, you can use the basic
  syntax in PHP (for the arrays or objects implementing the ``ArrayAccess`` 
  interface) the subscript (``[]``) or the object operator (``->``)::

    {var['some']->thing}
    {$var['some']->thing}

  .. warning::
    Keep in mind that it is not as flexible in PHP : avoid to use a "complex" 
    variable (containing an array key or a property) as a key or as a property. 
    For example, you may not do the following::

      {var[{$key->a['b']}]}

    This is due to some limitations brought by the way of parsing the templates
    (regular expressions), as it would bring down the performances of the 
    templating engine.

Escaping a variable
~~~~~~~~~~~~~~~~~~~
If you want to print out the ``{my_var}`` in you template without it being
parsed by Link (a "raw" display), you have to prefix it by a slash::

  \{my_var} {* will be rendered as "{my_var}" *}
  \{$my_var} {* will be rendered as "{$my_var}" *}

Constants
~~~~~~~~~
Like in PHP, you can access the declared constants in the application. To do that
the syntax is really simple::

  {__MY_CONSTANT__}
  {__$MY_CONSTANT__}

Filters
~~~~~~~
When working on variables (and special variables as you will see them in 
:ref:`their dedicated part <special-variables>`), you may want to apply some
transformations on them (like escaping them, or changing the case of a string)::

  {var|protect}
  {var|maximize}

You can also apply several filter on one entity::

  {var|maximize|protect}

The filters will be applied in the reverse of their order of declaration : in the
case mentionned above, the output should have the ``protect`` filter applied on the
result of the ``maximize`` filter applied on ``{var}``.

You may also use arguments on filters::

  {var|cut:40:...}

Here, the ``cut`` filter will be applied on ``{var}`` with a limit of 50 chars
and a finishing string ``...`` if the length of ``{var}`` exceeds 50 chars.

.. warning::
  There is another limitation for strings : you may not use the symbols ``:``
  or ``|``, as it would be interpreted as a new parameter or new filter, which
  could get the parser wrong. Once again, this is due to the parser, and trying
  to fix it would bring down performances.

List of pre-built filters
^^^^^^^^^^^^^^^^^^^^^^^^^
Here is the list of all the filters currently implemented by default in Link. 
It is not exhaustive, as this is not really the role of this document ; you may
find more exhaustive information about each filters in their dedicated chapter
(not yet written), or directly in the api documentation of the ``Link_Filters``
class.

=========== ====================================================================
Filter Name Description
=========== ====================================================================
ceil        Round fractions up
convertCase Perform case folding on a string
cut         Cut a string longer than $max characters. Words are not interrupted.
default     Gets a default value if it's ``empty``, ``false``, ... etc
floor       Round fractions down
invertCase  Perform a change of case on a string
lcfirst     Lowercase the first letter of a string
maximize    Make a string all UPPERCASE
minimize    Make a string all lowercase
nl2br       Inserts HTML line breaks before all newlines in a string
paragraphy  Smart convertion of newlines into <p> and <br />s
protect     Convert special characters to HTML entities
safe        Unescape a var -- useful if protect is an autofilter
slugify     Create the slug for a string, and send it back
ucfirst     UPPERCASE the first letter of a string
void        Just do... nothing.
=========== ====================================================================

Build your own filter
^^^^^^^^^^^^^^^^^^^^^
You may also build your own filter (and why not propose it as a built-in filter
via a Pull Request on `the GitHub repository <http://github.com/Taluu/Link-TPL>`_ !)
following some rules...

- You have to declare your filter in the ``Link_Filters`` class
- The first argument is the entity itself
- The declared method have to be ``public`` and ``static``

Let's say I want to implement a ``date`` filter ; here's how to do it::

  // in Link_Filters
  public static function date($arg, $format = 'd/m/Y') {
    if (!$arg instanceof DateTime) {
      $arg = new DateTime($arg);
    }

    return $arg->format($format);
  }

It's that simple ! :)

Control Structures
------------------
In Link, there are several types of xml-like tags that controls the whole logic
in Link's templates. We may control it via two types of tags : the conditonnal
tags and the loops ones.

Conditions
~~~~~~~~~~
In a template, it is not uncommon to print different things if the context
presents different cases. It is called a conditionnal templating, and Link
allows it via several tags : ``<if>`` tags, ``<elseif>`` tags, and ``<else>``
tags :

.. code-block:: xml

  <if condition="true === true">
    something is true
  <elseif condition="false !== true" />
    something is not true but not false either
  <else />
    everything is false
  </if>

In the ``condition`` attribute, may be written *any valid PHP condition*, with
some additions (like the use of Link variables). Also, you can use as many
``<elseif>`` tags you want, but only one ``<if>`` tag for a given ``<if>``
structure, and a maximum of one ``<else>`` tag. Here is a minimal condition :

.. code-block:: xml

  <if condition="isset({$my_var})">
    \{$my_var} is set ! :)
  </if>

.. note::
  
  There are several shortcuts here and there, specially on the conditions : for
  example, you may use ``cond`` instead of ``condition`` as the attribute of the
  conditionnal tags, or write ``<elif>`` instead of ``<elseif>``.

Loops
~~~~~
Also, the principle of a template means that when there are several datas to be
printed, we should avoid to repeat things (you know, the 
`DRY <http://en.wikipedia.org/wiki/DRY>`_ principle...). For that purpose, was
created in Link the ``<foreach>`` tag : when you use it, it waits for an array
to be iterated over and repeat a block of text as many times as there are
elements in the array.

.. code-block:: xml

  some random block of text

  <foreach array="{$my_array}">
    my text will be repeated as many times there are elements in \{$my_array}.
  </foreach>

Inside the ``<foreach>`` loop, you may access some other things, like a possibly
to do an alternate action if your array is empty (and will of course not be 
repeated...) :

.. code-block:: xml

  some random block of text

  <foreach array="{$my_array}">
    my text will be repeated as many times there are elements in \{$my_array}.
  <foreachelse />
    \{$my_array} is empty :(
  </foreach>

.. note::

  You may also remove the use of the delimiters ``{}`` for the variables in the
  ``array`` attribute. But be careful : it's either with these braces or without
  them !
  
And you have access to special variables... Let's see them now.

.. _special-variables:

Special variables
^^^^^^^^^^^^^^^^^
Six variables are created each times you loop over an array. Let's take 
``{$my_array}`` as an example.

- ``{$my_array.value}`` contains the value of the iteration itself
- ``{$my_array.key}`` contains the key of the iteration itself
- ``{$my_array.current}`` contains the current numbered iteration
- ``{$my_array.size}`` contains the size of the array that is iterated over
- ``{$my_array.is_first}`` checks that the current iteration is the first iteration
- ``{$my_array.is_last}`` checks that the current iteration is the last iteration

You may apply a suffix (array keys, object properties, filters, ...) on the 
value of the array (``{$my_array.value}``), print out (using ``{...}`` instead
of ``{$...}``) everything but the verification that it is or not the first or
the last iteration (as it does not have any senses to do otherwise).

.. note::

  Several shortcuts also exists for the ``foreach`` loops ; for example, you may
  use the ``ary`` attribute instead of the ``array`` attribute in the 
  ``<foreach>`` tag, use ``{$my_array.val}`` instead of ``{$my_array.value}``,
  or ``{$my_array.cur}`` instead of ``{$my_array.current}``.

The ``as`` attribute
^^^^^^^^^^^^^^^^^^^^
When iterating over complex variables (like a n-dimensionnal array), you may not
use the following ``<foreach array="{$my_array['an_item']}">`` (or anything more
than a simple variable name), but you should use the optionnal ``as`` attribute,
which is mandatory for this kind of use :

.. code-block:: xml

  <foreach array="{$my_array.value['test']}" as="{$this_array}">
    Now look, i'm using \{$this_array} : {this_array.value} ! :)
  </foreach>

Inclusions
----------
The include tag was made to include a template and render its content into the
current one.

.. code-block:: xml

  <include tpl="my/file.html.link" />

Per defaults, the included templated inherits its context from the current one.
You may also add other variables to the child template, adding them to its
context ; they're lost when we leave the template.

.. code-block:: xml

  <include tpl="my/file.html.link?abc=def&ghi=jkl" />

Here, the variables ``abc`` and ``ghi`` will be accessible in the included
template and all its children, but not in the current template.

You may also use a variable to indicate a template name (even though I would not
particularly recommend it) :

.. code-block:: xml

  <include tpl="{$my_var}" />

There is also another attribute for this tag, to be placed after the ``tpl``
attribute : the attribute ``once``. As its name implies it, its purpose is to
let the parser know that you wish to include the template once and only once. If
the template is already included somewhere else, then the template will not be
included. This attribute takes a boolean value. This attribute has a false value
per default.

.. code-block:: xml

  <include tpl="my/template.html.link" once="true" />

To conclude this part, you have also the ``require`` tag, behaving exactly like
the ``include`` tag : if the template is not found, then an error is thrown and
the script halts. With an ``include`` tag, only a message will be printed, but
the script will continue.

.. code-block:: xml

  <require tpl="my/file.html.link" />
