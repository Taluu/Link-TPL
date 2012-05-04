Link TPL for designers
======================

This chapter presents the syntax used in the templates and will mainly be useful
for the guys in charge of building the templates.

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
todo

Variables
---------
todo

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
