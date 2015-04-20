<?php if (isset($cat)) : ?>
<?php

$catPage = (isset($_GET['cat-page']) && !empty($_GET['cat-page'])) ? $_GET['cat-page'] : 1;
$maxItems = 15;
$offset = ($catPage-1)*$maxItems;

$countAllCategories = <<<EOT
  select count(*) total
  from 
    (select 
      MAX(p.post_date) max_date
    from 
      $wpdb->term_taxonomy tt,
      wp_terms t
        left join $wpdb->term_relationships tr on tr.term_taxonomy_id = t.term_id
        left join $wpdb->posts p on p.ID = tr.object_id
    where 
      tt.term_id = t.term_id
      and tt.taxonomy = 'category'
      and tt.parent = $cat
    group by t.term_id
    order by 
      t.term_order) cats;
EOT;

$selectAllCategories = <<<EOT
  select 
    *
  from
    (select 
      t.term_id,
      t.name term_name,
      MAX(p.post_date) max_date
    from 
      $wpdb->term_taxonomy tt,
      wp_terms t
        left join $wpdb->term_relationships tr on tr.term_taxonomy_id = t.term_id
        left join $wpdb->posts p on p.ID = tr.object_id
    where 
      tt.term_id = t.term_id
      and tt.taxonomy = 'category'
      and tt.parent = $cat
    group by t.term_id
    order by 
      t.term_order
    limit $offset,$maxItems) sub1

    left join (
      select *
      from 
        $wpdb->term_relationships tr,
        $wpdb->posts p
      where
        p.ID = tr.object_id
    ) sub2 
      on sub2.post_date = sub1.max_date 
      and sub2.term_taxonomy_id = sub1.term_id
EOT;

$countNonEmptyCategories = <<<EOT
  select count(*) total
  from
    (select 
      *
    from
      wp_term_relationships tr,
      wp_posts p,
      (select 
        t.term_id,
        t.name term_name,
        MAX(p.post_date) max_date
      from 
        $wpdb->term_taxonomy tt,
        wp_terms t
          left join $wpdb->term_relationships tr on tr.term_taxonomy_id = t.term_id
          left join $wpdb->posts p on p.ID = tr.object_id
      where 
        tt.term_id = t.term_id
        and tt.taxonomy = 'category'
        and tt.parent = $cat
      group by t.term_id
      order by 
        t.term_order) sub
    where
      p.ID = tr.object_id
      and tr.term_taxonomy_id = sub.term_id
      and p.post_date = sub.max_date) cats
EOT;

$selectNonEmptyCategories = <<<EOT
  select 
    *
  from
    wp_term_relationships tr,
    wp_posts p,
    (select 
      t.term_id,
      t.name term_name,
      MAX(p.post_date) max_date
    from 
      $wpdb->term_taxonomy tt,
      wp_terms t
        left join $wpdb->term_relationships tr on tr.term_taxonomy_id = t.term_id
        left join $wpdb->posts p on p.ID = tr.object_id
    where 
      tt.term_id = t.term_id
      and tt.taxonomy = 'category'
      and tt.parent = $cat
    group by t.term_id
    order by 
      t.term_order) sub
  where
    p.ID = tr.object_id
    and tr.term_taxonomy_id = sub.term_id
    and p.post_date = sub.max_date
  order by term_order
  limit $offset, $maxItems
EOT;

  $countCategories = $countNonEmptyCategories;
  $selectCategories = $selectNonEmptyCategories;

  $categoriesCount = $wpdb->get_results( $countCategories, OBJECT );
  $totalCategories = $categoriesCount[0]->total;

  $results = $wpdb->get_results( $selectCategories, OBJECT );

  $showPaging = ($totalCategories > $maxItems);
  $showNext   = $catPage*$maxItems < $totalCategories;
  $showPrev   = $catPage*$maxItems >= $totalCategories;
  
  $emptyItems = $showPaging ? $maxItems-count($results) : $maxItems+1-count($results);
?>

<!-- Grid items -->
<div class="grid cf">  
  <?php foreach ($results as $serie) : ?>
    <?php 
      $post = clone $serie;
      unset($post->term_id);
      unset($post->term_name);
      unset($post->max_date);
      setup_postdata( $post );
    ?>
    <div class="item">
        <a href="<?php echo get_category_link($serie->term_id); ?>">
          <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('thumbnail'); ?>
          <?php endif; ?>
        </a>
        <span class="label"><?=$serie->term_name;?></span>
    </div>
  <?php endforeach; ?>

  <?php for ($i=0; $i<$emptyItems; $i++) : ?>
    <div class="item">&nbsp;</div>
  <?php endfor; ?>

  <!-- paging -->
  <?php if ($showPaging) : ?>
    <div class="item paging">
      <?php if ($showNext) : ?><a href="?cat-page=<?=$catPage+1;?>" class="more"><span class="sign">&#43;</span> <span class="label">more</span></a><?php endif; ?>
      <?php if ($showPrev) : ?><a href="?cat-page=<?=$catPage-1;?>" class="less"><span class="sign">&#45;</span> <span class="label">less</span></a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php else : ?>

  <article class="content typeset">
    <p>
      <?php _e('Sorry, no results were found.', 'sage'); ?> 
    </p>
  </article>

<?php endif; ?>