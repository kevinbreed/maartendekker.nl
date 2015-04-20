<figure class="item">
  <?php if (has_post_thumbnail()) : ?>
    <?php $large_image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' ); ?>
    <a href="<?=array_shift($large_image);?>" class="fancybox" title="<?=strip_tags(get_the_content());?>" rel="figure">
      <?php the_post_thumbnail('thumbnail'); ?>
    </a>
  <?php endif; ?>
  <figcaption class="label"><?php the_title(); ?></figcaption>
</figure>
