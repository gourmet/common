<div class="navbar-collapse collapse">

	<?php
	echo $this->Navigation->render(array(
		array('path' => 'Top', 'attrs' => array('class' => 'nav navbar-nav')),
		array('path' => 'User', 'attrs' => array('class' => 'nav navbar-nav navbar-right'))
	));
	?>

</div>
