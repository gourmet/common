<?php
$__sidebar = $this->Navigation->render(
	array('Affiliate', 'Admin'),
	array('class' => 'nav-collapse collapse navbar-responsive-collapse span3 well')
);

if (!empty($__sidebar)) {
	echo '</div></div><div class="row-fluid">' . $__sidebar . '<div class="span9">';
}

unset($__sidebar);
