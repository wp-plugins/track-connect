<?php
/**
 * The template for displaying Listing Archive pages
 *
 * @link http://codex.wordpress.org/Template_Hierarchy
 *
 * @package Track Connect
 * @since 0.1.0
 */

$options = get_option('plugin_wp_listings_settings');
$checkin = ($_REQUEST['checkin'])? $_REQUEST['checkin']:false;
$checkout = ($_REQUEST['checkout'])? $_REQUEST['checkout']:false;
$bedrooms = ($_REQUEST['bedrooms'])? $_REQUEST['bedrooms']:false;
$availableUnits = false;
$checkAvailability = false;

if($checkin && $checkout){
    $checkAvailability = true;
    require_once( __DIR__ . '/../api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token']);
    $availableUnits = $request->getAvailableUnits($checkin,$checkout,$bedrooms);
}

function archive_listing_loop() {

		global $post,$availableUnits,$checkAvailability,$bedrooms;

		$count = 0; // start counter at 0

		// Start the Loop.
		while ( have_posts() ) : the_post();
		    $unitId = get_post_meta( $post->ID, '_listing_unit_id', true );
		    $bedroomSize = get_post_meta( $post->ID, '_listing_bedrooms', true );
		    if($checkAvailability){
                if(!in_array($unitId, $availableUnits)){
                    continue;   
                }
            }else{
                if($bedrooms && $bedroomSize != $bedrooms){
                    continue;
                }
            }
            
			$count++; // add 1 to counter on each loop
			$first = ($count == 1) ? 'first' : ''; // if counter is 1 add class of first
            
            /*
            https://d2epyxaxvaz7xr.cloudfront.net/250x250/
            https://d2epyxaxvaz7xr.cloudfront.net/759x470/
            */
			$loop = sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', get_permalink(), '<img src="https://d2epyxaxvaz7xr.cloudfront.net/305x208/'.get_post_meta( $post->ID, '_listing_first_image', true ).'"></img> ' );

			if ( '' != wp_listings_get_status() ) {
    			// Banner across thumb
				//$loop .= sprintf( '<span class="listing-status %s">%s</span>', strtolower(str_replace(' ', '-', wp_listings_get_status())), wp_listings_get_status() );
			}

			$loop .= sprintf( '<div class="listing-thumb-meta">' );

			if ( '' != get_post_meta( $post->ID, '_listing_text', true ) ) {
				$loop .= sprintf( '<span class="listing-text">%s</span>', get_post_meta( $post->ID, '_listing_text', true ) );
			} elseif ( '' != wp_listings_get_property_types() ) {
				$loop .= sprintf( '<span class="listing-property-type">%s</span>', wp_listings_get_property_types() );
			}
            
            $loop .= sprintf( '<span class="listing-property-type">%s</span>', 'starting at' );
            
			if ( '' != get_post_meta( $post->ID, '_listing_price', true ) ) {
				//$loop .= sprintf( '<span class="listing-price">%s</span>', get_post_meta( $post->ID, '_listing_price', true ) );
			}
            
            if ( '' != get_post_meta( $post->ID, '_listing_min_rate', true ) ) {
				$loop .= sprintf( '<span class="listing-price">%s/night</span>', get_post_meta( $post->ID, '_listing_min_rate', true ) );
			}
			
			$loop .= sprintf( '</div><!-- .listing-thumb-meta --></div><!-- .listing-widget-thumb -->' );

			if ( '' != get_post_meta( $post->ID, '_listing_open_house', true ) ) {
				//$loop .= sprintf( '<span class="listing-open-house">Open House: %s</span>', get_post_meta( $post->ID, '_listing_open_house', true ) );
			}
			
			if ( '' != get_post_meta( $post->ID, '_listing_city', true ) && '' != get_post_meta( $post->ID, '_listing_state', true ) ) {
				//$loop .= sprintf( '<span class="listing-open-house">%s, %s</span>', get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );
			}

			$loop .= sprintf( '<div class="listing-widget-details"><h3 class="listing-title"><a href="%s">%s</a></h3>', get_permalink(), get_the_title() );
			//$loop .= sprintf( '<p class="listing-address"><span class="listing-address">%s</span><br />', wp_listings_get_address() );
			//$loop .= sprintf( '<span class="listing-city-state-zip">%s, %s %s</span></p>', wp_listings_get_city(), wp_listings_get_state(), get_post_meta( $post->ID, '_listing_zip', true ) );
			$loop .= sprintf( '<p><span style="margin-left:12px;">%s, %s</span></p>', get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );
			//$loop .= sprintf( '<p><span style="margin-left:12px;">%s - %s</span></p>', get_post_meta( $post->ID, '_listing_min_rate', true ), get_post_meta( $post->ID, '_listing_max_rate', true ). ' per night' );
			 

			if ( '' != get_post_meta( $post->ID, '_listing_bedrooms', true ) || '' != get_post_meta( $post->ID, '_listing_bathrooms', true ) || '' != get_post_meta( $post->ID, '_listing_sqft', true )) {
				$loop .= sprintf( '<ul class="listing-beds-baths-sqft"><li class="beds">%s<span>Beds</span></li> <li class="baths">%s<span>Baths</span></li> <li class="sqft">%s<span>Occupancy</span></li></ul>', get_post_meta( $post->ID, '_listing_bedrooms', true ), get_post_meta( $post->ID, '_listing_bathrooms', true ), get_post_meta( $post->ID, '_listing_occupancy', true )  );
			}

			$loop .= sprintf('</div><!-- .listing-widget-details -->');

			$loop .= sprintf( '<a href="%s" class="button btn-primary more-link">%s</a>', get_permalink(), __( 'View Listing', 'wp_listings' ) );

			/** wrap in div with column class, and output **/
			printf( '<article id="post-%s" class="listing entry one-third %s"><div class="listing-wrap">%s</div><!-- .listing-wrap --></article><!-- article#post-## -->', get_the_id(), $first, apply_filters( 'wp_listings_featured_listings_widget_loop', $loop ) );

			if ( 3 == $count ) { // if counter is 3, reset to 0
				$count = 0;
			}

		endwhile;
		if (function_exists('equity')) {
			equity_posts_nav();
		} elseif (function_exists('genesis_init')) {
			genesis_posts_nav();
		} else {
			wp_listings_paging_nav();
		}

}

if (function_exists('equity')) {

	add_filter( 'equity_pre_get_option_site_layout', '__equity_return_full_width_content' );
	remove_action( 'equity_entry_header', 'equity_post_info', 12 );
	remove_action( 'equity_entry_footer', 'equity_post_meta' );

	remove_action( 'equity_loop', 'equity_do_loop' );
	add_action( 'equity_loop', 'archive_listing_loop' );

	equity();

} elseif (function_exists('genesis_init')) {

	add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
	remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );
	remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
	remove_action( 'genesis_entry_footer', 'genesis_post_meta' );
	remove_action( 'genesis_after_entry', 'genesis_do_author_box_single' );

	remove_action( 'genesis_loop', 'genesis_do_loop' );
	add_action( 'genesis_loop', 'archive_listing_loop' );

	genesis();

} else {

get_header(); ?>

	<section id="primary" class="content-area container inner">
		<div id="content" class="site-content" role="main">

			<?php if ( have_posts() ) : ?>

				<header class="archive-header">
					<?php
					$object = get_queried_object();

					if ( !isset($object->label) ) {
						//$title = '<h1 class="archive-title">' . $object->name . '</h1>';
					} else {
    					//get_bloginfo('name');
						
					}
                    $title = '<h1 class="archive-title">Unit Search</h1>';
					echo $title; ?>

                    <small><?php if ( function_exists('yoast_breadcrumb') ) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?></small>
				</header><!-- .archive-header -->

			<?php

			archive_listing_loop();

			else :
				// If no content, include the "No posts found" template.
				get_template_part( 'content', 'none' );

			endif;

			?>

		</div><!-- #content -->
	</section><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();

}