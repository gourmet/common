<?php

App::uses('DebugPanel', 'DebugKit.Lib');

class CommonPanel extends DebugPanel {

	public $elementName = 'panels/common_panel';

	public $plugin = 'Common';

	public function startup(Controller $Controller) {
		$this->title = __d('common', "Extras");
	}

	public function beforeRender(Controller $Controller) {

	}

}
