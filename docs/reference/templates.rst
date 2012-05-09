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
in Link's templates. We will see them right now.

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
    $my_var is set ! :)
  </if>

Loops
~~~~~
todo

.. _special-variables:

Special variables
^^^^^^^^^^^^^^^^^
todo

Inclusions
----------
todo
