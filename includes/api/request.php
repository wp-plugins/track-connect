<?php
namespace plugins\api;

class pluginApi{
	protected $endpoint;
	protected $client;
	protected $url;

	public function __construct($domain,$token){
		$this->token = $token;
        $this->domain = $domain;		
        $this->endpoint = (strtoupper($domain) == 'HSR')?'http://hsr.trackstaging.info':'https://'.strtolower($domain).'.trackhs.com';
        
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
                $wpdb->query("DELETE FROM wp_postmeta WHERE post_id = '".$post->id."' ;");
                $wpdb->query("DELETE FROM wp_posts WHERE id = '".$post->id."' ;"); 
            }     
        }
        $unitsRemoved = count($results);
            
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
    			$wpdb->query("DELETE FROM wp_postmeta WHERE post_id = '".$post_id."';");
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
                		( %d, %s, %s )
                	", 
                    array(
                        $post_id,'_listing_unit_id', $id,
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
                      'post_status' => 'publish',
                      'post_name' => $this->slugify($unit->name),
                      'post_type' => 'listing',
                );              
                wp_update_post( $my_post ); 
                  
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
                		( %d, %s, %s )
                	", 
                    array(
                        $post_id,'_listing_unit_id', $id,
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
                    )
                ));
  
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
			    'bedrooms'  => $bedrooms
			    )
			)
        );
        
		$unitArray = [];
		foreach(json_decode($units['body'])->response as $unit){
            $unitArray[] = $unit;
        }

        return $unitArray;
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

}