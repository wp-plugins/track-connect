<?php
/**
 * The Template for displaying all single listing posts
 *
 * @package Track Connect
 * @since 0.1.0
 */
 

if(get_post_status( $post->ID ) != 'publish' ){
	status_header(404);
	nocache_headers();
	include( get_404_template() );
	exit;
}

add_action('wp_enqueue_scripts', 'enqueue_single_listing_scripts');
function enqueue_single_listing_scripts() {
	wp_enqueue_style( 'wp-listings-single' );
	wp_enqueue_style( 'font-awesome' );
	wp_enqueue_style( 'slideshow' );
	wp_enqueue_script( 'jquery-validate', array('jquery'), true, true );
	wp_enqueue_script( 'jquery-slideshow', array('jquery'), true, true );
	wp_enqueue_script( 'jquery-slideshow-settings', array('jquery'), true, true );
	wp_enqueue_script( 'fitvids', array('jquery'), true, true );
	wp_enqueue_script( 'wp-listings-single', array('jquery, jquery-ui-tabs', 'jquery-validate'), true, true );
}

/** Set DNS Prefetch to improve performance on single listings templates */
add_filter('wp_head','wp_listings_dnsprefetch', 0);
function wp_listings_dnsprefetch() {
    echo "\n<link rel='dns-prefetch' href='//maxcdn.bootstrapcdn.com' />\n"; // Loads FontAwesome
    echo "<link rel='dns-prefetch' href='//cdnjs.cloudflare.com' />\n"; // Loads FitVids
}

function single_listing_post_content() {

	global $post;
    
    
    $options = get_option('plugin_wp_listings_settings');
    $trackServer = (strtoupper($options['wp_listings_domain']) == 'HSR')?"trackstaging.info":"trackhs.com";
    $imagesArray = json_decode(get_post_meta( $post->ID, '_listing_images')[0]);
    $amenitiesArray = json_decode(get_post_meta( $post->ID, '_listing_amenities')[0])
	?>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <style>
    .slide_wrapper {
    	width: 70%;
    	margin: 0 auto;
    }
    @media only screen and (max-device-width: 800px), screen and (max-width: 800px) {
      .slide_wrapper {
        width: 100%;
        margin: 75px;
      }  
    }
    .slide_block {
    	width: 100%;
    }
    .listing-wrapper {
        margin: 0px 75px 10px 75px;
        
    }
    .amenities {
        -moz-column-count: 4;
        -moz-column-gap: 20px;
        -webkit-column-count: 4;
        -webkit-column-gap: 20px;
        column-count: 4;
        column-gap: 20px;
    }
    </style>
    
        <?php
    		$listing_meta = sprintf( '<ul class="listing-meta">');
    
    		if ( '' != get_post_meta( $post->ID, '_listing_min_rate', true ) ) {
    			$listing_meta .= sprintf( '<li class="listing-price">%s to %s / night</li>', get_post_meta( $post->ID, '_listing_min_rate', true ), get_post_meta( $post->ID, '_listing_max_rate', true ) );
    		}
    
    		if ( '' != wp_listings_get_property_types() ) {
    			$listing_meta .= sprintf( '<li class="listing-property-type"><span class="label">Property Type: </span>%s</li>', get_the_term_list( get_the_ID(), 'property-types', '', ', ', '' ) );
    		}
    
    		if ( '' != get_post_meta( $post->ID, '_listing_city', true ) ) {
    			$listing_meta .= sprintf( '<li class="listing-location"><span class="label">Location: </span>%s, %s</li>', get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );
    		}
    
    		if ( '' != get_post_meta( $post->ID, '_listing_bedrooms', true ) ) {
    			$listing_meta .= sprintf( '<li class="listing-bedrooms"><span class="label">Beds: </span>%s</li>', get_post_meta( $post->ID, '_listing_bedrooms', true ) );
    		}
    
    		if ( '' != get_post_meta( $post->ID, '_listing_bathrooms', true ) ) {
    			$listing_meta .= sprintf( '<li class="listing-bathrooms"><span class="label">Baths: </span>%s</li>', get_post_meta( $post->ID, '_listing_bathrooms', true ) );
    		}
    
    		if ( '' != get_post_meta( $post->ID, '_listing_sqft', true ) ) {
    			$listing_meta .= sprintf( '<li class="listing-sqft"><span class="label">Sq Ft: </span>%s</li>', get_post_meta( $post->ID, '_listing_sqft', true ) );
    		}
    
    		if ( '' != get_post_meta( $post->ID, '_listing_lot_sqft', true ) ) {
    			$listing_meta .= sprintf( '<li class="listing-lot-sqft"><span class="label">Lot Sq Ft: </span>%s</li>', get_post_meta( $post->ID, '_listing_lot_sqft', true ) );
    		}
    
    		$listing_meta .= sprintf( '</ul>');
    
    		
    
    		?>
    		
    <div class="listing-wrapper">
        <div itemscope itemtype="http://schema.org/SingleFamilyResidence" class="entry-content wplistings-single-listing">
            
        <section class="slide_wrapper">
    		<article class="slide_block">
                <ul id="thumbnails">
                <?php $i = 0;
                    foreach($imagesArray as $image): $i++;?>
                    <li><a href="#slide<?=$i?>"><img src="https://d2epyxaxvaz7xr.cloudfront.net/620x475/<?=$image->url?>"></a></li>
                    <?php endforeach; ?>       
                </ul>
                <?=$listing_meta;?>
                <div class="thumb-box">
                    <ul class="thumbs">
                    <?php $i = 0;
                        foreach($imagesArray as $image): $i++;?>
                        <li><a href="#<?=$i?>" data-slide="<?=$i?>"><img src="https://d2epyxaxvaz7xr.cloudfront.net/125x85/<?=$image->url?>"></a></li> 
                        <?php endforeach; ?>  
                    </ul>
                </div>
    		</article>
        </section> 
            
    	                             
            
    		
    
    		<div id="listing-tabs" class="listing-data">
    
    			<ul>
        			<li><a href="#listing-availability">Availability</a></li>
        			
    				<li><a href="#listing-description">Description</a></li>
    
    				<li><a href="#listing-details">Details</a></li>
    				
                    <?php if(count($amenitiesArray)): ?>
                    <li><a href="#listing-amenities">Amenities</a></li>
                    <?php endif; ?>
                    
    				<?php if (get_post_meta( $post->ID, '_listing_gallery', true) != '') { ?>
    					<li><a href="#listing-gallery">Photos</a></li>
    				<?php } ?>
    
    				<?php if (get_post_meta( $post->ID, '_listing_video', true) != '') { ?>
    					<li><a href="#listing-video">Video / Virtual Tour</a></li>
    				<?php } ?>
                    <!--
    				<?php if (get_post_meta( $post->ID, '_listing_school_neighborhood', true) != '') { ?>
    				<li><a href="#listing-school-neighborhood">Schools &amp; Neighborhood</a></li>
    				<?php } ?>
    				-->
    			</ul>
                
                <div id="listing-availability" itemprop="availability">
                    <iframe frameborder="0" width="100%" height="550px" src="http://<?=$options['wp_listings_domain']?>.<?=$trackServer?>/api/vacation_rentals/index.php?cid=<?=get_post_meta( $post->ID, '_listing_unit_id', true )?>&domainweb=<?=$options['wp_listings_domain']?>&online_res=1"></iframe>
                </div>
            
    			<div id="listing-description" itemprop="description">
    				<?php the_content( __( 'View more <span class="meta-nav">&rarr;</span>', 'wp_listings' ) ); ?>
    			</div><!-- #listing-description -->
    
    			<div id="listing-details">
    				<?php
    					$details_instance = new WP_Listings();
    
    					$pattern = '<tr class="wp_listings%s"><td class="label">%s</td><td>%s</td></tr>';
    
    					echo '<table class="listing-details">';
    
                        echo '<tbody class="left">';
                        echo '<tr class="wp_listings_listing_price"><td class="label">Rates:</td><td>'.get_post_meta( $post->ID, '_listing_min_rate', true ) . ' to ' . get_post_meta( $post->ID, '_listing_max_rate', true ) .'</td></tr>';
                        echo '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';
                        echo '<tr class="wp_listings_listing_address"><td class="label">Address:</td><td itemprop="streetAddress">'.get_post_meta( $post->ID, '_listing_address', true) .'</td></tr>';
                        echo '<tr class="wp_listings_listing_city"><td class="label">City:</td><td itemprop="addressLocality">'.get_post_meta( $post->ID, '_listing_city', true) .'</td></tr>';
                        echo '<tr class="wp_listings_listing_state"><td class="label">State:</td><td itemprop="addressRegion">'.get_post_meta( $post->ID, '_listing_state', true) .'</td></tr>';
                        echo '<tr class="wp_listings_listing_zip"><td class="label">Zip:</td><td itemprop="postalCode">'.get_post_meta( $post->ID, '_listing_zip', true) .'</td></tr>';
                        echo '</div>';
                        echo '<tr class="wp_listings_listing_mls"><td class="label">Max Occupancy:</td><td>'.get_post_meta( $post->ID, '_listing_occupancy', true) .'</td></tr>';
                        echo '</tbody>';
    
    					echo '<tbody class="right">';
    					foreach ( (array) $details_instance->property_details['col2'] as $label => $key ) {
    						$detail_value = esc_html( get_post_meta($post->ID, $key, true) );
    						if (! empty( $detail_value ) ) :
    							printf( $pattern, $key, esc_html( $label ), $detail_value );
    						endif;
    					}
    					echo '</tbody>';
    
    					echo '</table>';
    
    				?>
    
    			</div><!-- #listing-details -->
                
                <?php if(count($amenitiesArray)): ?>
                <div id="listing-amenities">
                   <ul class="amenities" >
                   <?php foreach($amenitiesArray as $amenity){
                        if($amenity->type && $amenity->type == 'boolean' && $amenity->number == 1){ $val =  $amenity->name; }
                        if($amenity->type && $amenity->type == 'text' && $amenity->number > 0){ $val = $amenity->name . ': '.$amenity->number; }
                        if(!$amenity->type){ $val = $amenity->name; }
                        if($val == ''){ continue; };
                        echo '<li>●'.$val.'</li>';
                   } ?> 
                   </ul>
                </div>
                <?php endif; ?>
                
    			<?php if (get_post_meta( $post->ID, '_listing_gallery', true) != '') { ?>
    			<div id="listing-gallery">
    				<?php echo do_shortcode(get_post_meta( $post->ID, '_listing_gallery', true)); ?>
    			</div><!-- #listing-gallery -->
    			<?php } ?>
    
    			<?php if (get_post_meta( $post->ID, '_listing_video', true) != '') { ?>
    			<div id="listing-video">
    				<div class="iframe-wrap">
    				<?php echo get_post_meta( $post->ID, '_listing_video', true); ?>
    				</div>
    			</div><!-- #listing-video -->
    			<?php } ?>
    
    			<?php if (get_post_meta( $post->ID, '_listing_school_neighborhood', true) != '') { ?>
    			<div id="listing-school-neighborhood">
    				<p>
    				<?php echo do_shortcode(get_post_meta( $post->ID, '_listing_school_neighborhood', true)); ?>
    				</p>
    			</div><!-- #listing-school-neighborhood -->
    			<?php } ?>
    
    		</div><!-- #listing-tabs.listing-data -->
    
    		<?php
    			if (get_post_meta( $post->ID, '_listing_map', true) != '') {
    			echo '<div id="listing-map"><h3>Location Map</h3>';
    			echo do_shortcode(get_post_meta( $post->ID, '_listing_map', true) );
    			echo '</div><!-- .listing-map -->';
    			}
    		?>
    
    		<?php
    			if (function_exists('_p2p_init') && function_exists('agent_profiles_init') ) {
    				echo'<div id="listing-agent">
    				<div class="connected-agents">';
    				aeprofiles_connected_agents_markup();
    				echo '</div></div><!-- .listing-agent -->';
    			}
    		?>        
                   
    	</div><!-- .entry-content -->
    </div><!-- .listing-wrapper -->
<?php
}

if (function_exists('equity')) {

	remove_action( 'equity_entry_header', 'equity_post_info', 12 );
	remove_action( 'equity_entry_footer', 'equity_post_meta' );

	remove_action( 'equity_entry_content', 'equity_do_post_content' );
	add_action( 'equity_entry_content', 'single_listing_post_content' );

	equity();

} elseif (function_exists('genesis_init')) {

	remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );
	remove_action( 'genesis_entry_header', 'genesis_post_info', 12 ); // HTML5
	remove_action( 'genesis_before_post_content', 'genesis_post_info' ); // XHTML
	remove_action( 'genesis_entry_footer', 'genesis_post_meta' ); // HTML5
	remove_action( 'genesis_after_post_content', 'genesis_post_meta' ); // XHTML
	remove_action( 'genesis_after_entry', 'genesis_do_author_box_single', 8 ); // HTML5
	remove_action( 'genesis_after_post', 'genesis_do_author_box_single' ); // XHTML

	remove_action( 'genesis_entry_content', 'genesis_do_post_content' ); // HTML5
	remove_action( 'genesis_post_content', 'genesis_do_post_content' ); // XHTML
	add_action( 'genesis_entry_content', 'single_listing_post_content' ); // HTML5
	add_action( 'genesis_post_content', 'single_listing_post_content' ); // XHTML

	genesis();

} else {

get_header(); ?>

	<div id="primary" class="content-area container inner">
		<div id="content" class="site-content" role="main">

			<?php
				// Start the Loop.
				while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<header class="entry-header">
						<?php the_title( '<h1 class="entry-title" itemprop="name">', '</h1>' ); ?>
						<small><?php if ( function_exists('yoast_breadcrumb') ) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?></small>
						<div class="entry-meta">
							<?php
								if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
							?>
							<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'wp_listings' ), __( '1 Comment', 'wp_listings' ), __( '% Comments', 'wp_listings' ) ); ?></span>
							<?php
								endif;

								edit_post_link( __( 'Edit', 'wp_listings' ), '<span class="edit-link">', '</span>' );
							?>
						</div><!-- .entry-meta -->
					</header><!-- .entry-header -->


				<?php single_listing_post_content(); ?>

				</article><!-- #post-ID -->

			<?php
				// Previous/next post navigation.
				wp_listings_post_nav();

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				endwhile;
			?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
}
?>