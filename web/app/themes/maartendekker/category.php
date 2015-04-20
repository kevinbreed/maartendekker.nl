<?php 
	$childCategories = get_categories(array('parent' => $cat));
	if (count($childCategories) > 0) {
		include( __DIR__.'/templates/category-overview.php' );
	}
	else {
		include(__DIR__.'/index.php');
	}