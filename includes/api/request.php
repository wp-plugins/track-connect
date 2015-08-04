<?php
namespace plugins\api;

class pluginApi{
	protected $endpoint;
	protected $client;
	protected $url;

	public function __construct($domain,$token){
		$this->token = $token;
        $this->domain = $domain;		
        $this->endpoint = (strtoupper($domain) == 'HSR')?"trackstaging.info":"trackhs.com";
        //$this->endpoint = 'http://'.$domain.'.jreed.trackhs.com';
	}
    
	public function getUnits(){
		global $wpdb;
        
        $unitsCreated = 0;
        $unitsUpdated = 0;
        
		$units = wp_remote_post($this->endpoint.'/api/wordpress/units/',
		array(
			'timeout'     => 500,
			'body' => array( 
    			'token'     => $this->token
			    )
			)
        );
        
        
        
		foreach(json_decode($units['body'])->response as $id => $unit){

			if (!isset($unit->occupancy) || $unit->occupancy == 0) {
				$occupancy =  isset($unit->rooms) && $unit->rooms >= 1 ? $unit->rooms * 2 : 2;
			} else {
				$occupancy = $unit->occupancy;
			}

			$timestamp = new \DateTime('now');            
            
			//$wpdb->replace('wp_postmeta',$insertArray);
			$post = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_listing_unit_id' AND meta_value = '".$id."' LIMIT 1;");
			if($post->post_id > 0){
    			$unitsUpdated++;
    			$post_id = $post->post_id;
    			$wpdb->query("DELETE FROM wp_postmeta WHERE post_id = '".$post_id."';");
    			$wpdb->query("INSERT INTO wp_postmeta (post_id,meta_key,meta_value) VALUES 
                    ('".$post_id."', '_listing_unit_id', '".$id."' ),
                    ('".$post_id."', '_listing_bedrooms', '".$unit->rooms."' ),
                    ('".$post_id."', '_listing_bathrooms', '".$unit->bath."' ),
                    ('".$post_id."', '_listing_images', '".json_encode($unit->images)."' ),
                    ('".$post_id."', '_listing_amenities', '".json_encode($unit->amenities)."' ),
                    ('".$post_id."', '_listing_address', '".$unit->address."' ),
                    ('".$post_id."', '_listing_city', '".$unit->city."' ),
                    ('".$post_id."', '_listing_state', '".$unit->state."' ),
                    ('".$post_id."', '_listing_zip', '".$unit->zip."' ),
                    ('".$post_id."', '_listing_occupancy', '".$occupancy."' ),
                    ('".$post_id."', '_listing_min_rate', '$".$unit->min_rate."' ),
                    ('".$post_id."', '_listing_max_rate', '$".$unit->max_rate."' ),
                    ('".$post_id."', '_listing_first_image', '".$unit->images[0]->url."' )  ;");
                
                $post_status = 'publish';
    			if(!$unit->enabled_online){
                    $post_status = 'draft';
                }
                
                $wpdb->query("UPDATE wp_posts SET 
                    post_author = 1,
    			    comment_status = 'closed',
    			    ping_status = 'closed',
    			    post_modified = NOW(),
    			    post_modified_gmt = NOW(),  			    
    			    post_content = '".$unit->description."',
    			    post_title = '".$unit->name."',
    			    post_status = '".$post_status."',
    			    post_name = '".$this->slugify($unit->name)."',
    			    post_type = 'listing' 
    			    WHERE ID = '".$post_id."';");
                  
                //Update the Status
                $term = $wpdb->get_row("SELECT term_id FROM wp_terms WHERE name = 'Active' AND slug = 'active';");
                $wpdb->query("DELETE FROM wp_term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = '".$term->term_id."';");
                if($unit->enabled_online){            
                    $wpdb->query("INSERT INTO wp_term_relationships set 
                        object_id = '".$post_id."',
                        term_taxonomy_id = '".$term->term_id."';");
                }
                
			}else{
    			$post_status = 'publish';
    			if(!$unit->enabled_online){
                    continue;
                }
                $unitsCreated++;
    			$wpdb->query("INSERT INTO wp_posts set 
    			    post_author = 1,
    			    comment_status = 'closed',
    			    ping_status = 'closed',
    			    post_date = NOW(),
    			    post_date_gmt = NOW(),
    			    post_modified = NOW(),
    			    post_modified_gmt = NOW(),  			    
    			    post_content = '".$unit->description."',
    			    post_title = '".$unit->name."',
    			    post_status = '".$post_status."',
    			    post_name = '".$this->slugify($unit->name)."',
    			    post_type = 'listing';");
                
                $post_id = $wpdb->insert_id;
                
                $wpdb->query("INSERT INTO wp_postmeta (post_id,meta_key,meta_value) VALUES 
                    ('".$post_id."', '_listing_unit_id', '".$id."' ),
                    ('".$post_id."', '_listing_bedrooms', '".$unit->rooms."' ),
                    ('".$post_id."', '_listing_bathrooms', '".$unit->bath."' ),
                    ('".$post_id."', '_listing_images', '".json_encode($unit->images)."' ),
                    ('".$post_id."', '_listing_amenities', '".json_encode($unit->amenities)."' ),
                    ('".$post_id."', '_listing_address', '".$unit->address."' ),
                    ('".$post_id."', '_listing_city', '".$unit->city."' ),
                    ('".$post_id."', '_listing_state', '".$unit->state."' ),
                    ('".$post_id."', '_listing_zip', '".$unit->zip."' ),
                    ('".$post_id."', '_listing_occupancy', '".$occupancy."' ),
                    ('".$post_id."', '_listing_min_rate', '$".$unit->min_rate."' ),
                    ('".$post_id."', '_listing_max_rate', '$".$unit->max_rate."' ),
                    ('".$post_id."', '_listing_first_image', '".$unit->images[0]->url."' ) ;");                  
                    
                //Create the Status    
                $term = $wpdb->get_row("SELECT term_id FROM wp_terms WHERE name = 'Active' AND slug = 'active';");
                $wpdb->query("INSERT INTO wp_term_relationships set 
                    object_id = '".$post_id."',
                    term_taxonomy_id = '".$term->term_id."';");
			}			

		}
       
		return "Created: $unitsCreated. Updated: $unitsUpdated.";
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