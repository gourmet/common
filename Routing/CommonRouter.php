<?php

App::uses('Router', 'Routing');

class CommonRouter extends Router {

	public static function reload() {
		$defaultRouteClass = parent::defaultRouteClass();
		parent::reload();
		parent::defaultRouteClass($defaultRouteClass);
	}

}
