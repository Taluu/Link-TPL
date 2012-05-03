Link TPL for designers
======================

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

