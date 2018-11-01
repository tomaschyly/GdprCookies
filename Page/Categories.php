<?php
/*
Template Name: Privacy Policy Categories page
Template Post Type: page
*/
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

get_header (); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		while (have_posts ()): the_post ();
			new \ChylyGDPRCookiesView\Categories ();

			if (comments_open () || get_comments_number ()) {
				comments_template ();
			}
		endwhile; ?>
	</main>
</div>

<?php get_sidebar (); ?>
<?php get_footer (); ?>
