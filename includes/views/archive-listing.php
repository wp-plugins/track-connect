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
$debug = ($_REQUEST['track_debug'])? $_REQUEST['track_debug']:0;
$availableUnits = false;
$checkAvailability = false;
$linkString = '';

if($checkin && $checkout){
    $linkString = "?checkin=$checkin&checkout=$checkout";
    $checkAvailability = true;
    require_once( __DIR__ . '/../api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'],$debug);
    $availableUnits = $request->getAvailableUnits($checkin,$checkout,false);  
}
session_start();

// Retrieve consistent random set of posts with pagination
function mam_posts_query($query) {
   global $mam_posts_query;
   if ($mam_posts_query && strpos($query, 'ORDER BY RAND()') !== false) {
      $query = str_replace('ORDER BY RAND()',$mam_posts_query,$query);
   }
   return $query;
}


function wpbeginner_numeric_posts_nav() {
    // alternative paging, not used anymore
    
	if( is_singular() )
		return;

	global $wp_query;

	/** Stop execution if there's only 1 page */
	if( $wp_query->max_num_pages <= 1 )
		return;

	$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
	$max   = intval( $wp_query->max_num_pages );

	/**	Add current page to the array */
	if ( $paged >= 1 )
		$links[] = $paged;

	/**	Add the pages around the current page to the array */
	if ( $paged >= 3 ) {
		$links[] = $paged - 1;
		$links[] = $paged - 2;
	}

	if ( ( $paged + 2 ) <= $max ) {
		$links[] = $paged + 2;
		$links[] = $paged + 1;
	}

	echo '<div class="navigation-link"><ul>' . "\n";

	/**	Previous Post Link */
	if ( get_previous_posts_link() )
		printf( '<li>%s</li>' . "\n", get_previous_posts_link() );

	/**	Link to first page, plus ellipses if necessary */
	if ( ! in_array( 1, $links ) ) {
		$class = 1 == $paged ? ' class="active"' : '';

		printf( '<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( 1 ) ), '1' );

		if ( ! in_array( 2, $links ) )
			echo '<li>…</li>';
	}

	/**	Link to current page, plus 2 pages in either direction if necessary */
	sort( $links );
	foreach ( (array) $links as $link ) {
		$class = $paged == $link ? ' class="active"' : '';
		printf( '<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $link ) ), $link );
	}

	/**	Link to last page, plus ellipses if necessary */
	if ( ! in_array( $max, $links ) ) {
		if ( ! in_array( $max - 1, $links ) )
			echo '<li>…</li>' . "\n";

		$class = $paged == $max ? ' class="active"' : '';
		printf( '<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $max ) ), $max );
	}

	/**	Next Post Link */
	if ( get_next_posts_link() )
		printf( '<li>%s</li>' . "\n", get_next_posts_link() );

	echo '</ul></div>' . "\n";

}


function archive_listing_loop() {

		global $post,$wp_query,$wp_the_query,$availableUnits,$checkAvailability,$bedrooms,$checkin,$checkout,$mam_posts_query;

		$count = 0; // start counter at 0
        $unitsAvailable = true;
		
        if($checkAvailability && $availableUnits['success'] == false){
            echo '<div align="center" style="padding:25px;">'.$availableUnits['message'].'</div>';
            $unitsAvailable = false;
        }
		
		$avgRates = null;
		$avgRates = $availableUnits['rates'];
		
		// Start the Loop.	
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		$args = array(
		    'post_type'         => 'listing',
		    'posts_per_page'    => 15,
		    'paged'             => $paged,
		    'order'             => 'ASC',
		    'orderby'           => 'rand'
        );       
        
		add_filter('query','mam_posts_query');
		$seed = date('G');       
        /*
	    $seed = $_SESSION['seed'];
        if (empty($seed)) {
          $seed = rand();          
          $_SESSION['seed'] = $seed;
        }
        */
        $mam_posts_query = " ORDER BY rand($seed) "; // Turn on filter
      

		if($bedrooms > 0){    		
    		$args += array('meta_key' => '_listing_bedrooms','meta_value' => $bedrooms);
		}
		if($checkAvailability){    		
    		$args += array('post__in' => $availableUnits['units']);
		}  
		if(get_query_var('status') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'status',
        			'field'             => 'slug',
        			'terms'             => get_query_var('status')
        		),
                ),);
		}
		if(get_query_var('features') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'features',
        			'field'             => 'slug',
        			'terms'             => get_query_var('features')
        		),
                ),);
		}
		if(get_query_var('locations') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'locations',
        			'field'             => 'slug',
        			'terms'             => get_query_var('locations')
        		),
                ),);
		}
		if(get_query_var('property-types') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'property-types',
        			'field'             => 'slug',
        			'terms'             => get_query_var('property-types')
        		),
                ),);
		}

		//query_posts($args);
        $wp_query = new WP_Query();
        $wp_query->query($args);
        $mam_posts_query = ''; // Turn off filter
              
		if ( have_posts() && $unitsAvailable ) : 
		    while ( have_posts() ) : the_post();
		    //$post = $query->post;

		    $unitId = get_post_meta( $post->ID, '_listing_unit_id', true );
		    $bedroomSize = get_post_meta( $post->ID, '_listing_bedrooms', true );
		    
		    $link = get_permalink();
		    if($checkin && $checkout){
                $link = add_query_arg( 'checkin', $checkin, get_permalink() );
                $link = add_query_arg( 'checkout', $checkout, $link );
            }
            
			$count++; // add 1 to counter on each loop
			$first = ($count == 1) ? 'first' : ''; // if counter is 1 add class of first
            $firstImage = get_post_meta( $post->ID, '_listing_first_image', true );
            
			$loop = sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', $link, '<img src="https://d2epyxaxvaz7xr.cloudfront.net/305x208/'.get_post_meta( $post->ID, '_listing_first_image', true ).'"></img> ' );
            
            if($firstImage == '' || $firstImage === null){
                $loop = sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', $link, '<img src="http://placehold.it/305x208">' );  
            }
            
			if ( wp_listings_get_featured()  ) {
    			// Banner across thumb
				$loop .= sprintf( '<span class="listing-status %s">Featured</span>', strtolower(str_replace(' ', '-', wp_listings_get_status())), wp_listings_get_status() );
			}

			$loop .= sprintf( '<div class="listing-thumb-meta">' );
            
            if ( $avgRates[$unitId] > 0 ) {
                $loop .= sprintf( '<span class="listing-property-type">%s</span>', 'avg. rate' );
				$loop .= sprintf( '<span class="listing-price">$%s/night</span>', number_format($avgRates[$unitId],0) );
			}else{
				$loop .= sprintf( '<span class="listing-property-type">%s</span>', 'starting at' );
				$loop .= sprintf( '<span class="listing-price">$%s/night</span>', number_format(get_post_meta( $post->ID, '_listing_min_rate', true ),0) );
			}
			
			$loop .= sprintf( '</div><!-- .listing-thumb-meta --></div><!-- .listing-widget-thumb -->' );


			$loop .= sprintf( '<div class="listing-widget-details"><h3 class="listing-title"><a href="%s">%s</a></h3>', $link, get_the_title() );

			$loop .= sprintf( '<p class="listing-information">%s BR / %s BA / %s PPL - %s, %s</p>', get_post_meta( $post->ID, '_listing_bedrooms', true ), get_post_meta( $post->ID, '_listing_bathrooms', true ), get_post_meta( $post->ID, '_listing_occupancy', true ),  get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );
            
            $loop .= sprintf( '<p class="listing-overview">%s</p>', get_post_meta( $post->ID, '_listing_overview', true ) );
            
            //$loop .= sprintf( '<span style="margin-left:14px;"><a href="%s" class="button btn-primary">%s</a><span>', $link, __( 'View Property', 'wp_listings' ) );
            
			$loop .= sprintf('</div><!-- .listing-widget-details -->');

			

			/** wrap in div with column class, and output **/
			printf( '<article id="post-%s" class="listing entry listing-box %s"><div class="listing-wrap">%s</div><!-- .listing-wrap --></article><!-- article#post-## -->', get_the_id(), $first, apply_filters( 'wp_listings_featured_listings_widget_loop', $loop ) );

			if ( 3 == $count ) { // if counter is 3, reset to 0
				$count = 0;
			}

		endwhile;
		
		else:
		    echo '<div align="center" style="padding:25px;">No units are available with the selected filters.</div>';
		endif;
        
        if($unitsAvailable){
            wp_listings_paging_nav();
            //wpbeginner_numeric_posts_nav();
        }
}



get_header(); ?>
    
    <style>
    .listing-widget-thumb {
        width: 30%; 
        float:left;
        max-width: 250px;
    }
    .listing-widget-details {
        margin-top: -18px;
        width: 70%;
        float:left;
        border: 0px !important;
        background: none !important;
    }   
    .listing-box {
        width: 100%;
        height: 225px;
        padding-bottom:20px; 
        padding-left:20px; 
        padding-top:12px;
    }
    .listing-information {
        padding-left: 12px;
    }
    .listing-overview {
        padding-left: 12px;
    }
    .listing-title{
        padding-left: 12px !important;
    }
        
     @media only screen and (max-device-width: 800px), screen and (max-width: 800px) {
        .listing-widget-thumb {
            width: 100%;
        }
        .listing-widget-details {
            width: 100%;
        } 
        .listing-box {
            margin-bottom:20px; 
        }
        .listing-information {
            padding-left: 0px;
        }
        .listing-title{
            padding-left: 0px !important;
        }
        .listing-overview{
            padding-left: 0px;
        }
    }
    
    .navigation-link li a,
    .navigation-link li a:hover,
    .navigation-link li.active a,
    .navigation-link li.disabled {
    	color: #fff;
    	text-decoration:none;
    }
    
    .navigation-link li {
    	display: inline;
    }
    
    .navigation-link li a,
    .navigation-link li a:hover,
    .navigation-link li.active a,
    .navigation-link li.disabled {
    	background-color: #6FB7E9;
    	border-radius: 3px;
    	cursor: pointer;
    	padding: 12px;
    	padding: 0.75rem;
    }
    
    .navigation-link li a:hover,
    .navigation-link li.active a {
    	background-color: #3C8DC5;
    }
    
    </style>
    
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