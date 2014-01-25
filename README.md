# CakePHP Common Plugin

Built to help quickly putting together [CakePHP][cakephp] applications.

Apart from being the base to all other [gourmet plugins][gourmet], this plugin
extends some of the [CakePHP][cakephp] core functionality (i.e. full support of
logging scopes, more complete base test case, better app configuration, etc.).

## Features

* Easily [extendable using events](wiki/Events).

* Simplified test writing.

* Advanced application configuration.

* Out of the box support for [other popular CakePHP plugins](wiki/Other-Popular-Plugins).

### Behaviors

* [Authorizable](wiki/Authorizable-Behavior): Simple and easy-to-use model record access management.

* [Duplicatable](wiki/Duplicatable-Behavior): Duplicates any model record to another model's table.

* [Encodable](wiki/Encodable-Behavior): Several encoding methods for storing arrays in model's table.

* [Totalizable](wiki/Totalizable-Behavior): Calculates totals similarly to the [CakePHP][cakephp]'s `counterCache`.

### Helpers

* [Table](wiki/Table-Helper): Creates table for displaying result sets, with pagination support.

## Install

### Composer package

First, add this plugin as a requirement to your `composer.json`:

	{
		"require": {
			"cakephp/common": "*"
		}
	}

And then update:

	php composer.phar update

That's it! You should now be ready to start configuring your channels.

### Submodule

	$ cd /app
	$ git submodule add git://github.com/gourmet/common.git Plugin/Common

### Clone

	$ cd /app/Plugin
	$ git clone git://github.com/gourmet/common.git

## Configuration

You need to enable the plugin your `app/Config/bootstrap.php` file:

	CakePlugin::load('Common', array('bootstrap' => true, 'routes' => true));

If you are already using `CakePlugin::loadAll();`, then this is not necessary.

Replace `app/Console/Command/AppShell.php` with the following:

	<?php
	App::uses('CommonAppShell', 'Common.Console');
	class AppShell extends CommonAppShell {}

Replace `app/Controller/AppController.php` with the following:

	<?php
	App::uses('CommonAppController', 'Common.Controller');
	class AppController extends CommonAppController {}

Replace `app/Model/AppModel.php` with the following:

	<?php
	App::uses('CommonAppModel', 'Common.Model');
	class AppModel extends CommonAppModel {}

Replace `app/View/Helper/AppHelper.php` with the following:

	<?php
	App::uses('CommonAppHelper', 'Common.View/Helper');
	class AppHelper extends CommonAppHelper {}

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

## Bugs & Feedback

http://github.com/gourmet/common/issues

## License

Copyright 2013, [Jad Bitar][jadbio]

Licensed under [The MIT License][mit]<br/>
Redistributions of files must retain the above copyright notice.

[cakephp]:http://cakephp.org
[gourmet]:http://github.com/gourmet
[jadbio]:http://jadb.io
[mit]:http://www.opensource.org/licenses/mit-license.php
