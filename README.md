# CakePHP Common Plugin

Built to help quickly putting together [CakePHP][cakephp] applications.

Apart from being the base to all other [gourmet plugins][gourmet], this plugin
extends some of the [CakePHP][cakephp] core functionality (i.e. full support of
logging scopes, more complete base test case, better app configuration, etc.).

## Features

* Easily [extendable using events](/gourmet/common/wiki/Events).

* Simplified test writing.

* Advanced application configuration.

* Out of the box support for [other popular CakePHP plugins](/gourmet/common/wiki/Other-Popular-Plugins).

### Behaviors

* [Authorizable](/gourmet/common/wiki/Authorizable-Behavior): Controls access to records.

* [Commentable](/gourmet/common/wiki/Commentable-Behavior): Adds comments to records.

* [Computable](/gourmet/common/wiki/Computable-Behavior): Computes total or average similarly to the [CakePHP][cakephp]'s `counterCache`.

* [Confirmable](/gourmet/common/wiki/Confirmable-Behavior): Adds `isConfirmed()` validation rule.

* [Detailable](/gourmet/common/wiki/Detailable-Behavior): Extends any record with extra details.

* [Duplicatable](/gourmet/common/wiki/Duplicatable-Behavior): Duplicates any record to another model's table.

* [Encodable](/gourmet/common/wiki/Encodable-Behavior): Encodes array values in records for storing.

* [Filterable](/gourmet/common/wiki/Filterable-Behavior): Filters out any field from both, the `find()` resultset and `save()` result.

* [Stateable](/gourmet/common/wiki/Stateable-Behavior): Adds `status` type of field to records.

### Components

* [Opauth](/gourmet/common/wiki/Opauth-Component): Adds the missing component from the [CakePHP Opauth](/uzyn/cakephp-opauth) plugin.

* [PersistentValidation](/gourmet/common/wiki/PersistentValidation-Component): Persists validation errors after a redirect.


### Helpers

* [Asset](/gourmet/common/wiki/Asset-Helper): Controls asset inclusions (works well with [AssetCompress](/markstory/asset_compress)).

* [Navigation](/gourmet/common/wiki/Navigation-Helper): Renders navigations.

* [Stateable](/gourmet/common/wiki/Stateable-Helper): Adds `status` type of selection list.

* [Table](/gourmet/common/wiki/Table-Helper): Creates table for displaying result sets, with pagination support.

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
