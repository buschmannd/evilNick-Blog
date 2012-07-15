<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */
?>

	</div><!-- #main -->

	<footer id="colophon" role="contentinfo">

			<?php
				/* A sidebar in the footer? Yep. You can can customize
				 * your footer with three columns of widgets.
				 */
				if ( ! is_404() )
					get_sidebar( 'footer' );
			?>

			<div id="site-generator">
				
	Wanna follow ? :
	<div class="buttonwrap">		
		
<div id="buttons" >
<img src="<?php echo home_url( '/wp-content/themes/eviltwo/images/ico24X24/facebook.png' ); ?>" align="bottom" alt="facebook" width="" height="" />
</div>
<div id="buttons">
<img src="<?php echo home_url( '/wp-content/themes/eviltwo/images/ico24X24/feed.png' ); ?>" align="bottom" alt="facebook" width="" height="" />
</div>
<div id="buttons">
<img src="<?php echo home_url( '/wp-content/themes/eviltwo/images/ico24X24/google.png' ); ?>" align="bottom" alt="facebook" width="" height="" />
</div>
<div id="buttons">
<img src="<?php echo home_url( '/wp-content/themes/eviltwo/images/ico24X24/twitter.png' ); ?>" align="bottom" alt="facebook" width="" height="" />
</div>
<div id="buttons">
<img src="<?php echo home_url( '/wp-content/themes/eviltwo/images/ico24X24/wordpress.png' ); ?>" align="bottom" alt="facebook" width="" height="" />
</div>
<div id="buttons">
<img src="<?php echo home_url( '/wp-content/themes/eviltwo/images/ico24X24/youtube.png' ); ?>" align="bottom" alt="facebook" width="" height="" />
</div></div>	
				
			</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>