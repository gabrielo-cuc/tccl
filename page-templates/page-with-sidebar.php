<?php
/**
 * Template Name: Page with sidebar
 */

get_header(); ?>

	<div id="content" class="site-content">

		<?php while ( have_posts() ) : the_post(); ?>

			<?php
			$thumb_url   = get_the_post_thumbnail_url( $post, 'full' );
			$title       = get_the_title();   // Could use 'the_title()' but this allows for override
			$description = ( $post->post_excerpt ) ? get_the_excerpt() : ''; // Could use 'the_excerpt()' but this allows for override
			$sidebar     = get_post_meta( get_the_ID(), 'page_sidebar_content', true ); // Could use 'the_excerpt()' but this allows for override
			$hero_class = $thumb_url ? 'ccl-c-hero ccl-has-image' : 'ccl-c-hero';
			?>

			<article <?php post_class(); ?>>

				<div class="<?php echo esc_attr( $hero_class ); ?>" style="background-image:url(<?php echo esc_url( $thumb_url ); ?>)">
					
					<div class="ccl-l-container">

						<div class="ccl-l-row">

							<div class="ccl-l-column ccl-l-span-third-lg">
								<div class="ccl-c-hero__header">
									<h1 class="ccl-c-hero__title"><?php echo apply_filters( 'the_title', $title ); ?></h1>
								</div>
							</div>

							<div class="ccl-l-column ccl-l-span-two-thirds-lg">
								<div class="ccl-c-hero__content">
									<div class="ccl-h4 ccl-u-mt-0"><?php echo apply_filters( 'the_excerpt', $description ); ?></div>
								</div>
							</div>

						</div>

					</div>

				</div>

				<div class="ccl-l-container">

					<div class="ccl-l-row">
						<div class="ccl-c-entry-content ccl-l-column ccl-l-span-9-md ccl-u-my-2">
							<?php the_content(); ?>
						</div>

						<div class="ccl-c-entry-sidebar ccl-l-column ccl-l-span-3-md ccl-u-my-2">
							<?php echo apply_filters( 'the_content', $sidebar ); ?>
						</div>
					</div>

				</div>

				<?php get_template_part( 'partials/blocks' ); ?>

			</article>

			<?php 
			//get the related posts from template part
			get_template_part( 'partials/related-posts' ); ?>

		<?php endwhile; ?>

	</div>

<?php get_footer();