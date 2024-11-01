<article id="post-<?php the_ID(); ?>" <?php post_class("swiftpost-teaser-sideimg"); ?>>
		 <div class="entry-thumb">
                <?php
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( array(496, 346) ); // 496 x 346
                }
                ?>
         </div>
         <div class="entry-content">
                <header>

                    <?php // show categories only for post
                    if ( 'post' == $post->post_type ) { ?>
                        <span class="entry-categories"><?php  the_category(', '); ?></span>
                    <?php } ?>

                    <h4 class="entry-title clearfix">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <div class="meta-box">
                        <span class="entry-date"><a href="<?php echo get_permalink(); ?>"><?php the_time( get_option( 'date_format' ) ); ?></a></span>

                        <?php if ( 'post' == $post->post_type ) { ?>
                            <span class="entry-author">By  <?php the_author_posts_link(); ?></span>
                        <?php } // endif ?>

                        <?php if(comments_open()){ 
                    		echo '<span class="comments-link">';
			                comments_popup_link( sprintf( __( 'Leave a comment<span class="screen-reader-text"> on %s</span>', 'swiftpost' ), get_the_title() ) );
			                echo '</span>';
                    	 } 
                    	 ?>
                    </div>
                    <!-- meta-box -->

                    <div class="clear"></div>
                </header>
                 <?php the_excerpt(); ?>
        </div>
        <div style="clear:both;"></div>
 </article>
