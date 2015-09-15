<?php
namespace plugins\api;

class pluginApi{
	protected $endpoint;
	protected $client;
	protected $url;

	public function __construct($domain,$token,$debug = 0){
		$this->token = $token;
        $this->domain = $domain;	
        $this->debug = 	$debug;
        $this->endpoint = (strtoupper($domain) == 'HSR')?'http://hsr.trackstaging.info':'https://'.strtolower($domain).'.trackhs.com';
        //$this->endpoint = 'http://hsr.jreed.trackhs.com';
	}
    
    public function getEndPoint(){
        return $this->endpoint;
    }
    
	public function getUnits(){
		global $wpdb;
        
        $domain = strtolower($this->domain);
        $unitsCreated = 0;
        $unitsUpdated = 0;
        $unitsRemoved = 0;

		$units = wp_remote_post($this->endpoint.'/api/wordpress/units/',
		array(
			'timeout'     => 500,
			'body' => array( 
    			'token'     => $this->token
			    )
			)
        );

        // Clean out other domain units        
        $results = $wpdb->get_results("SELECT post_id as id FROM wp_postmeta WHERE _listing_domain != '".$domain."' GROUP BY post_id;");
        if(count($results)){
            foreach($results as $post){
                //$wpdb->query("DELETE FROM wp_postmeta WHERE post_id = '".$post->id."' AND meta_key != '_thumbnail_id' ;");
                //$wpdb->query("DELETE FROM wp_posts WHERE id = '".$post->id."' ;"); 
            }     
        }
        $unitsRemoved = count($results);
		
		if($this->debug == 1){
			print_r(json_decode($units['body']));
		}
		
		foreach(json_decode($units['body'])->response as $id => $unit){
        
			if (!isset($unit->occupancy) || $unit->occupancy == 0) {
				$occupancy =  isset($unit->rooms) && $unit->rooms >= 1 ? $unit->rooms * 2 : 2;
			} else {
				$occupancy = $unit->occupancy;
			}
            
            if(count($unit->images)){
                usort($unit->images, function($a, $b) {
                    return $a->rank - $b->rank;
                });
            }                      
            $today = date('Y-m-d H:i:s');
            
			$post = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_listing_unit_id' AND meta_value = '".$id."' LIMIT 1;");
			if($post->post_id > 0){
    			$unitsUpdated++;
    			$post_id = $post->post_id;
    			$youtube_id = null;
    			$youtube = $wpdb->get_row("SELECT meta_value FROM wp_postmeta WHERE meta_key = '_listing_youtube_id' LIMIT 1;");
    			if($youtube->meta_value){
    				$youtube_id = $youtube->meta_value;
    			}
    			$wpdb->query("DELETE FROM wp_postmeta WHERE post_id = '".$post_id."' AND meta_key != '_thumbnail_id'  ;");
    			$wpdb->query( $wpdb->prepare( 
                	"
                		INSERT INTO $wpdb->postmeta
                		( post_id, meta_key, meta_value )
                		VALUES 
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s )
                	", 
                    array(
                        $post_id,'_listing_unit_id', $id,
                        $post_id,'_listing_overview', $unit->overview,
                        $post_id,'_listing_bedrooms', $unit->rooms,
                        $post_id,'_listing_bathrooms', $unit->bath,
                        $post_id,'_listing_images', json_encode($unit->images),
                        $post_id,'_listing_amenities', json_encode($unit->amenities),
                        $post_id,'_listing_address', $unit->address,
                        $post_id,'_listing_city', $unit->city,
                        $post_id,'_listing_state', $unit->state,
                        $post_id,'_listing_zip', $unit->zip,
                        $post_id,'_listing_occupancy', $occupancy,
                        $post_id,'_listing_min_rate', $unit->min_rate,
                        $post_id,'_listing_max_rate', $unit->max_rate,
                        $post_id,'_listing_domain', $domain,
                        $post_id,'_listing_first_image', $unit->images[0]->url,
                        $post_id,'_listing_youtube_id', (!$youtube_id)?null:$youtube_id
                    )
                ));
                
                $my_post = array(
                      'ID'           => $post_id,
                      'post_title'   => $unit->name,
                      'post_content' => $unit->description,
                      'post_author'  => 1,
                      'comment_status' => 'closed',
                      'ping_status' => 'closed',
                      'post_modified' => $today,
                      'post_modified_gmt' => $today,
                      'post_name' => $this->slugify($unit->name),
                      'post_type' => 'listing',
                );              
                wp_update_post( $my_post );
                
                // Create image
                $image = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;");
                if(!$image->post_id && $unit->images[0]->url && $unit->images[0]->url > ''){
                    $this->createImage($post_id,$unit->images[0]->url);
                }
                  
                //Update the Status
                $term = $wpdb->get_row("SELECT term_id FROM wp_terms WHERE name = 'Active' AND slug = 'active';");
                $wpdb->query("DELETE FROM wp_term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = '".$term->term_id."';");
                $wpdb->query("INSERT INTO wp_term_relationships set 
                    object_id = '".$post_id."',
                    term_taxonomy_id = '".$term->term_id."';");
                
			}else{
                $unitsCreated++;
                
                $wpdb->query( $wpdb->prepare( 
                	"
                		INSERT INTO $wpdb->posts
                		( post_author, comment_status, ping_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_content, post_title, post_status, post_name, post_type)
                		VALUES 
                		( %d, %s, %s,  %s,  %s,  %s,  %s,  %s,  %s,  %s,  %s,  %s )
                	", 
                	array(
                        1,'closed','closed',$today,$today,$today,$today,$unit->description,$unit->name,'publish',$this->slugify($unit->name),'listing',
                	)
                ));
                $post_id = $wpdb->insert_id;
                                
                $wpdb->query( $wpdb->prepare( 
                	"
                		INSERT INTO $wpdb->postmeta
                		( post_id, meta_key, meta_value )
                		VALUES 
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s )
                	", 
                    array(
                        $post_id,'_listing_unit_id', $id,
                        $post_id,'_listing_overview', $unit->overview,
                        $post_id,'_listing_bedrooms', $unit->rooms,
                        $post_id,'_listing_bathrooms', $unit->bath,
                        $post_id,'_listing_images', json_encode($unit->images),
                        $post_id,'_listing_amenities', json_encode($unit->amenities),
                        $post_id,'_listing_address', $unit->address,
                        $post_id,'_listing_city', $unit->city,
                        $post_id,'_listing_state', $unit->state,
                        $post_id,'_listing_zip', $unit->zip,
                        $post_id,'_listing_occupancy', $occupancy,
                        $post_id,'_listing_min_rate', $unit->min_rate,
                        $post_id,'_listing_max_rate', $unit->max_rate,
                        $post_id,'_listing_domain', $domain,
                        $post_id,'_listing_first_image', $unit->images[0]->url,
                        $post_id,'_listing_youtube_id', null
                    )
                ));
                
                // Create image
                $image = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;");
                if(!$image->post_id && $unit->images[0]->url && $unit->images[0]->url > ''){
                    $this->createImage($post_id,$unit->images[0]->url);
                }
                
                //Create the Status    
                $term = $wpdb->get_row("SELECT term_id FROM wp_terms WHERE name = 'Active' AND slug = 'active';");
                $wpdb->query("INSERT INTO wp_term_relationships set 
                    object_id = '".$post_id."',
                    term_taxonomy_id = '".$term->term_id."';");
			}			

		}
       
		return "Created: $unitsCreated. Updated: $unitsUpdated. Removed: $unitsRemoved";
	}
    
    public function getAvailableUnits($checkin,$checkout,$bedrooms = false){
		global $wpdb;
		
		$checkin = date('Y-m-d', strtotime($checkin));
		$checkout = date('Y-m-d', strtotime($checkout));
		
		$units = wp_remote_post($this->endpoint.'/api/wordpress/available-units/',
		array(
			'timeout'     => 500,
			'body' => array( 
    			'token'     => $this->token,
			    'checkin'   => $checkin, 
			    'checkout'  => $checkout,
			    'bedrooms'  => false
			    )
			)
        );

		$unitArray = [];
		
		if($this->debug == 1){
			print_r(json_decode($units['body']));
		}
		
		if(json_decode($units['body'])->success == false){
			return [
				'success' => false,
				'message' => json_decode($units['body'])->message
			];
		}
		foreach(json_decode($units['body'])->response->available as $cid=>$avgRate){
    		$query = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_listing_unit_id' AND meta_value = '".$cid."' LIMIT 1; ");
            $unitArray[] = $query->post_id;
            $rateArray[$cid] = $avgRate;

        }		
		
        return [
        	'success' => true,
        	'units'   => $unitArray,
        	'rates'	  => $rateArray
        ];
    }
    
    public function getReservedDates($unitId){
		global $wpdb;
		
		$units = wp_remote_post($this->endpoint.'/api/wordpress/unavailable-dates/',
		array(
			'timeout'     => 500,
			'body' => array( 
    			'token'     => $this->token,
			    'unit_id'   => $unitId
			    )
			)
        );

        return json_decode($units['body'])->response;
    }
    
    public function getQuote($unitId,$checkin,$checkout,$persons){
		
		$quote = wp_remote_post($this->endpoint.'/api/wordpress/quote/',
		array(
			'timeout'     => 500,
			'body' => array( 
    			'token'     => $this->token,
			    'cid'       => $unitId,
			    'checkin'   => $checkin,
			    'checkout'  => $checkout,
			    'persons'   => $persons
			    )
			)
        );

        return json_decode($quote['body']);
    }
    
	static public function slugify($text){
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim
		$text = trim($text, '-');

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text)){
			return 'n-a';
		}

		return $text;
	}
	
	public function createImage($post_id,$url){
    	// Add Featured Image to Post
        $image_url  = $url; // Define the image URL here
        $upload_dir = wp_upload_dir(); // Set upload folder
        $image_data = file_get_contents($image_url); // Get image data
        $filename   = basename($image_url); // Create image file name
        
        // Check folder permission and define file location
        if( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        
        // Create the image  file on the server
        file_put_contents( $file, $image_data );
        
        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );
        
        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        
        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
        
        // Include image.php
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        
        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );
        
        // And finally assign featured image to post
        set_post_thumbnail( $post_id, $attach_id );
	}

}