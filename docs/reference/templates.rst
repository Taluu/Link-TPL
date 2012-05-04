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

As you may see, there are typically three types of instructions : ``{var}``,
representing a variable and printing it, ``{$var}``, representing the variable
itself, and several xml-like tags like ``<foreach>``, ``<if>``, ... containing
the logic of the template.

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

To print it out, you have to use the following (for let's say a `var` variable)::

  {var}

And to use the variable as an entity, you will need to use the following::

  {$var}

.. note::
  As in PHP, if your variable is an object, you may want to access its properties,
  or if your variable is an array its components. For that, you can use the basic
  syntax in PHP (for the arrays or objects implementing the `ArrayAccess` 
  interface) the subscript (`[]`) or the object operator (`->`)::

    {var['some']->thing}
    {$var['some']->thing}

  .. warning::
    But do keep in mind that it is not as flexible in PHP : please avoid to use
    a "complex" variable (containing an array key) as a key or as a property. 
    For example, you may not do the following::

      {var[{$key->a['b']}]}

    This is due to some limitations brought by the way of parsing the templates
    (regular expressions).

Constants
~~~~~~~~~
todo

Filters
~~~~~~~
todo

Control Structures
------------------
todo

Conditions
~~~~~~~~~~
todo

Loops
~~~~~
todo

Special variables
^^^^^^^^^^^^^^^^^
todo

Inclusions
----------
todo
