<?php if (!have_posts()) : ?>
  <article class="content typeset">
    <p>
      <?php _e('Sorry, no results were found.', 'sage'); ?> 
    </p>
  </article>
<?php else : ?>
  <div class="grid cf">
    <?php 
      $prev = get_previous_posts_link(__('<span class="sign">&#45;</span> <span class="label">less</span>')); 
      $next = get_next_posts_link(__('<span class="sign">&#43;</span> <span class="label">more</span>'));
      $hasPaging = $next || $prev;
      $itemsLeft = $hasPaging ? 15 : 16;
    ?>
    <?php while (have_posts()) : the_post(); $itemsLeft--; ?>
      <?php get_template_part('templates/content', get_post_type() != 'post' ? get_post_type() : get_post_format()); ?>
    <?php endwhile; ?>
    <?php for ($i=0; $i<$itemsLeft; $i++) : ?>
      <div class="item">&nbsp;</div>
    <?php endfor; ?>
    <?php if ($hasPaging) : ?>
      <div class="item paging">
        <?=preg_replace('/<a href="([^"]*)"/', '<a href="$1" class="more"', $next);?>
        <?=preg_replace('/<a href="([^"]*)"/', '<a href="$1" class="less"', $prev);?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>