<?php
class EM_Taxonomy extends EM_Object {
	//Class-overridable options
	public $option_name = 'tag';
	//Taxonomy Fields
	var $id = '';
	var $term_id;
	var $name;
	var $slug;
	var $term_group;
	var $term_taxonomy_id;
	var $taxonomy;
	var $description = '';
	var $parent = 0;
	var $count;
	//extra attributes imposed by EM Taxonomies
	var $image_url = '';
	var $color;

	
	function get_color(){
		if( empty($this->color) ){
			global $wpdb;
			$color = $wpdb->get_var('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$this->term_id}' AND meta_key='". $this->option_name ."-bgcolor' LIMIT 1");
			$this->color = ($color != '') ? $color:get_option('dbem_'.$this->option_name.'_default_color', '#FFFFFF');
		}
		return $this->color;
	}
	
	function get_image_url( $size = 'full' ){
		if( empty($this->image_url) ){
			global $wpdb;
			$image_url = $wpdb->get_var('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$this->term_id}' AND meta_key='". $this->option_name ."-image' LIMIT 1");
			$this->image_url = ($image_url != '') ? $image_url:'';
		}
		return $this->image_url;
	}
	
	function get_image_id(){
		if( empty($this->image_id) ){
			global $wpdb;
			$image_id = $wpdb->get_var('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$this->term_id}' AND meta_key='". $this->option_name ."-image-id' LIMIT 1");
			$this->image_id = ($image_id != '') ? $image_id:'';
		}
		return $this->image_id;
	}
	
	function placeholder_image( $replace, $placeholders, $key ){	
		if( $this->get_image_url() != ''){
			$image_url = esc_url($this->get_image_url());
			if( empty($placeholders[3][$key]) ){
				$replace = "<img src='".esc_url($this->get_image_url())."' alt='".esc_attr($this->name)."'/>";
			}else{
				$image_size = explode(',', $placeholders[3][$key]);
				if( self::array_is_numeric($image_size) && count($image_size) > 1 ){
					if( $this->get_image_id() ){
						//get a thumbnail
						if( get_option('dbem_disable_thumbnails') ){
							$image_attr = '';
							$image_args = array();
							if( empty($image_size[1]) && !empty($image_size[0]) ){
								$image_attr = 'width="'.$image_size[0].'"';
								$image_args['w'] = $image_size[0];
							}elseif( empty($image_size[0]) && !empty($image_size[1]) ){
								$image_attr = 'height="'.$image_size[1].'"';
								$image_args['h'] = $image_size[1];
							}elseif( !empty($image_size[0]) && !empty($image_size[1]) ){
								$image_attr = 'width="'.$image_size[0].'" height="'.$image_size[1].'"';
								$image_args = array('w'=>$image_size[0], 'h'=>$image_size[1]);
							}
							$replace = "<img src='".esc_url(em_add_get_params($image_url, $image_args))."' alt='".esc_attr($this->name)."' $image_attr />";
						}else{
							//since we previously didn't store image ids along with the url to the image (since taxonomies don't allow normal featured images), sometimes we won't be able to do this, which is why we check there's a valid image id first
							self::ms_global_switch();
							$replace = wp_get_attachment_image($this->get_image_id(), $image_size);
							self::ms_global_switch_back();
						}
					}
				}else{
					$replace = "<img src='".esc_url($this->get_image_url())."' alt='".esc_attr($this->name)."'/>";
				}
			}
		}
		return $replace;
	}
}