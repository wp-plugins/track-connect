<?php
/**
 * This widget creates a search form which uses listings' taxonomy for search fields.
 *
 * @package WP Listings
 * @since 0.1.0
 */
class WP_Listings_Search_Widget extends WP_Widget {
   
    // used to be WP_Listings_Search_Widget
	function WP_Listings_Search_Widget() {
		$widget_ops = array( 'classname' => 'listings-search wp-listings-search wp-listings-search-sidebar', 'description' => __( 'Display listings search dropdown', 'wp_listings' ) );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'listings-search' );
		$this->__construct( 'listings-search', __( 'WP Listings - Search', 'wp_listings' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
        
        $checkin = ($_REQUEST['checkin'])? $_REQUEST['checkin']:'';
        $checkout = ($_REQUEST['checkout'])? $_REQUEST['checkout']:'';
        $rooms = ($_REQUEST['bedrooms'])? $_REQUEST['bedrooms']:'';
        
        //wp_enqueue_script('jquery');
        //wp_enqueue_script('bootstrap-scripts', get_template_directory_uri().'/includes/js/bootstrap-datepicker.min.js');
        
		$instance = wp_parse_args( (array) $instance, array(
			'title'			=> '',
			'button_text'	=> __( 'Search Listings', 'wp_listings' )
		) );

		global $_wp_listings_taxonomies,$post,$wp_query,$wp_the_query;

		$listings_taxonomies = $_wp_listings_taxonomies->get_taxonomies();

		extract( $args ); 

		echo $before_widget;

		if ( $instance['title'] ) echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $after_title;

		echo '<form role="search" method="get" id="searchform" action="' . home_url( '/' ) . '" ><input type="hidden" value="" name="s" /><input type="hidden" value="listing" name="post_type" />';
		echo '<input type="text" name="checkin" id="checkin" class="datepicker listing-dates" placeholder="Arrival" value="'.$checkin.'">';
        echo '<input type="text" name="checkout" id="checkout" class="datepicker listing-dates" placeholder="Departure" value="'.$checkout.'">';
        
        echo '<select name="bedrooms" id="bedrooms" class="listing-bedrooms">';
        echo '<option value="">Bedrooms</option>';
        for($i=1;$i < 13;$i++){
            $selected = ($rooms == $i)? 'SELECTED' : '';
            echo '<option '.$selected.' value="'.$i.'">'.$i.'</option>';
        }
        echo '</select>';
        
		foreach ( $listings_taxonomies as $tax => $data ) {
			if ( ! isset( $instance[$tax] ) || ! $instance[$tax] )
				continue;
			$terms = get_terms( $tax, array( 'orderby' => 'title', 'number' => 100, 'hierarchical' => false ) );
			if ( empty( $terms ) )
				continue;
				
            //echo ($wp_query->query_vars['taxonomy']);
            //print_r($wp_query->query_vars['tax_query'][0]['terms']); 
            //echo $wp_query->query_vars['taxonomy'][$tax];
			$current = ! empty( $wp_query->query_vars['tax_query'][0]['terms'] ) ? $wp_query->query_vars['tax_query'][0]['terms'] : '';
			echo "<select name='$tax' id='$tax' class='wp-listings-taxonomy'>\n\t";
			echo '<option value="" ' . selected( $current == '', true, false ) . ">{$data['labels']['name']}</option>\n";
			foreach ( (array) $terms as $term )
				echo "\t<option value='{$term->slug}' " . selected( $current, $term->slug, false ) . ">{$term->name}</option>\n";
			echo '</select>';
		}
        
        
		echo '<div class="btn-search"><button type="submit" class="searchsubmit"><i class="fa fa-search"></i><span class="button-text">'. esc_attr( $instance['button_text'] ) .'</span></button></div>';
		echo '<div class="clear"></div>
		</form>';

		echo $after_widget;
		?>
		<script type="text/javascript">
        jQuery(function($) {
			
			/*
            $('.datepicker').on('click', function() {
                $(this).datepicker({ dateFormat: "mm/dd/yy" });
                $(this).datepicker('show');
            });
            */
            
            $( "#checkin" ).datepicker({
		      defaultDate: "+1w",
		      changeMonth: true,
		      numberOfMonths: 1,
		      onClose: function( selectedDate ) {
		        $( "#checkout" ).datepicker( "option", "minDate", selectedDate );
		      },
		      onSelect: function(dateText, inst) { 
    		        var date2 = $('#checkin').datepicker('getDate', '+1d'); 
                      date2.setDate(date2.getDate()+1); 
                      $('#checkout').datepicker('setDate', date2);
			  }
		    });
		    $( "#checkout" ).datepicker({
		      defaultDate: "+1w",
		      changeMonth: true,
		      numberOfMonths: 1,
		      onClose: function( selectedDate ) {
		        $( "#checkin" ).datepicker( "option", "maxDate", selectedDate );
		      }
		    });
			

        });
        </script>
        <?php

	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
        //wp_enqueue_script( 'jquery-ui-datepicker' );
        //wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
        
        
    
		$instance = wp_parse_args( (array) $instance, array(
			'title'			=> '',
			'button_text'	=> __( 'Search Listings', 'wp_listings' )
		) );

		global $_wp_listings_taxonomies;

		$listings_taxonomies = $_wp_listings_taxonomies->get_taxonomies();
		$new_widget = empty( $instance );

		printf( '<p><label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" style="%s" /></p>', $this->get_field_id( 'title' ), __( 'Title:', 'wp_listings' ), $this->get_field_id( 'title' ), $this->get_field_name( 'title' ), esc_attr( $instance['title'] ), 'width: 95%;' );
		?>
		<h5><?php _e( 'Include these taxonomies in the search widget', 'wp_listings' ); ?></h5>
		<?php
		foreach ( (array) $listings_taxonomies as $tax => $data ) {
			$terms = get_terms( $tax );
			if ( empty( $terms ) )
				continue;
			
			$checked = isset( $instance[ $tax ] ) && $instance[ $tax ];

			printf( '<p><label><input id="%s" type="checkbox" name="%s" value="1" %s />%s</label></p>', $this->get_field_id( 'tax' ), $this->get_field_name( $tax ), checked( 1, $checked, 0 ), esc_html( $data['labels']['name'] ) );

		}

		printf( '<p><label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" style="%s" /></p>', $this->get_field_id( 'button_text' ), __( 'Button Text:', 'wp_listings' ), $this->get_field_id( 'button_text' ), $this->get_field_name( 'button_text' ), esc_attr( $instance['button_text'] ), 'width: 95%;' );
		
		?>
		<script type="text/javascript">
        ( function($) {

            $('.datepicker').on('click', function() {
                $(this).datepicker({ dateFormat: "mm/dd/yy" });
                $(this).datepicker('show');
            });

        })(jQuery);
        </script>
        <?php
	}
}