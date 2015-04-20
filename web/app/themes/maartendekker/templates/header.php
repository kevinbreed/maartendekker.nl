<?php 
  use Roots\Sage\Nav\NavWalker; 
  use Roots\Sage\Titles;

  $menuLocations = get_nav_menu_locations();
  $menuItems = wp_get_nav_menu_items($menuLocations['primary_navigation']);
  $displayCatTitle = true;

  foreach ($menuItems as $menuItem) {
    if ($menuItem->object_id == $cat) {
      $displayCatTitle = false;
      break;
    }
  }
?>

<header class="cf<?=(!$displayCatTitle ? ' no-title' : '')?>">
  <h1><a href="<?= esc_url(home_url('/')); ?>"><?php echo strtolower(get_bloginfo('name')) ?></a></h1>
  <nav class="main-nav cf">
    <?php
      if (has_nav_menu('primary_navigation')) :
        wp_nav_menu(array('theme_location' => 'primary_navigation', 'walker' => new NavWalker(), 'menu_class' => 'cf'));
      endif;
    ?>
  </nav>
  <?php if (is_category() && $displayCatTitle) : ?><h2><?=Titles\title();?></h2><?php endif; ?>
</header>
