<article id="post-<?php the_ID(); ?>" <?php post_class("swiftpost-full"); ?>>
		
			<header class="entry-header">
			<?php
				the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
	
				if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
	               	printf('Posted: <time class="entry-date published" datetime="%1$s"> %2$s</time>  ',  esc_attr( get_the_modified_date( 'c' ) ),get_the_modified_date() );
               	} else {
               		printf('Posted: <time class="entry-date published" datetime="%1$s"> %2$s</time>  ',  esc_attr( get_the_date( 'c' ) ),get_the_date() );
               	}
		
		        if ( 'post' == get_post_type() && (is_singular() || is_multi_author()) ) {
					printf( '<span class="byline"><span class="author vcard"> by %1$s</span></span>',get_the_author() );
		        }
				?>
			</header><!-- .entry-header -->
		
		<?php
		if ( has_post_thumbnail() ) {
	        ?>
	        <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
	                <?php the_post_thumbnail( 'post-thumbnail', array( 'alt' => get_the_title() ) ); ?>
	        </a>

        <?php } ?>



	<div class="entry-content">
		
		<?php
			the_content( "Read more..." );
			wp_link_pages( array(
				'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'swiftpost' ) . '</span>',
				'after'       => '</div>',
				'link_before' => '<span>',
				'link_after'  => '</span>',
				'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'swiftpost' ) . ' </span>%',
				'separator'   => '<span class="screen-reader-text">, </span>',
			) );
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
	<?php
 
        if ( comments_open() || get_comments_number() ) {
                echo '<span class="comments-link">';
                /* translators: %s: post title */
                comments_popup_link( sprintf( __( 'Leave a comment<span class="screen-reader-text"> on %s</span>', 'swiftpost' ), get_the_title() ) );
                echo '</span>';
        }

		 edit_post_link( __( 'Edit', 'swiftpost' ), ' <span class="edit-link">', '</span> ' ); ?>
	</footer><!-- .entry-footer -->

</article><!-- #post-## -->