<?php 
	use Roots\Sage\Nav\NavWalker; 
	use Roots\Sage\Titles;
?>

<header class="cf">
  <h1><a href="<?= esc_url(home_url('/')); ?>"><?php echo strtolower(get_bloginfo('name')) ?></a></h1>
  <nav class="main-nav cf">
    <?php
      if (has_nav_menu('primary_navigation')) :
        wp_nav_menu(['theme_location' => 'primary_navigation', 'walker' => new NavWalker(), 'menu_class' => 'cf']);
      endif;
    ?>
  </nav>
  <?php if (is_category()) : ?><h2><?=Titles\title();?></h2><?php endif; ?>
</header>
