.. index::
   single: Installation

Installation
============

To install the package, put the following in your composer.json:

.. code-block:: json

	"require": {
		"franzose/closure-table": "dev-master"
	}


And to ``app/config/app.php``:

.. code-block:: php

	'providers' => array(
		// ...
		'Franzose\ClosureTable\ClosureTableServiceProvider',
	),