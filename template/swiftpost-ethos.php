<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <?php if ( $is_index_featured_layout ) : ?>
    <?php x_ethos_featured_index(); ?>
  <?php else : ?>
    <?php if ( has_post_thumbnail() ) : ?>
      <div class="entry-featured">
        <?php if ( ! is_single() ) : ?>
          <?php x_ethos_featured_index(); ?>
        <?php else : ?>
          <?php x_featured_image(); ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="entry-wrap">
      <?php x_get_view( 'ethos', '_content', 'post-header' ); ?>
      <?php x_get_view( 'global', '_content' ); ?>
    </div>
  <?php endif; ?>
</article>