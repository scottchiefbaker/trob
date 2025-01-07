Trob
====

Trob is a small PHP framework with the following components:

1. [Smarty](https://www.smarty.net/)
2. [Krumo](https://github.com/mmucklo/krumo)
3. [DBQuery](https://github.com/scottchiefbaker/dbquery)
4. [JQuery](https://jquery.com/)
5. [Bootstrap](https://getbootstrap.com/)

The primary goal is to provide a simple template based PHP platform with access to common tools. All of these tools are optional, but enabled by default to facilitate rapid development.

Usage
-----

Clone this repo and point a new **.php** file at Trob

```php
include("/path/to/trob/trob.class.php");
$trob = new trob();

$trob->assign("output", "Hello world!");
$trob->display();
```

Then create a smarty template file in a `tpls/` directory with a `.stpl` extension.
