<?php
/**
 * @package thumbnailsforbackend
 * @author G100g
 * @version 0.0.4
 */
/*
Plugin Name: Thumbnails for Backend
Plugin URI: http://g100g.net/wordpress-stuff/thumbnails-for-backend-plugin/
Description: Simple plugin to add thumbnails to your Posts list within the WordPress backend.
Author: G100g
Version: 0.0.4
Author URI: http://g100g.net/

	Copyright (C) 2011 by Giorgio Aquino
	
	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.

*/

class Thumbnailsforbackend {

	var $thumbfb_options = array(
								'thumbfb_post_types' => array()
								);

	function __construct() {
		$this->get_options();
	}
	
	function get_options() {
		$this->thumbfb_options = unserialize( get_option('thumbfb_options') );
		return $this->thumbfb_options;
	}
	
	function add_thumbnails() {
		
		$this->get_options();
			
		$showing_thumbnail = FALSE;
		
		if (is_array($this->thumbfb_options["thumbfb_post_types"])) { 
		
			foreach ($this->thumbfb_options["thumbfb_post_types"] as $post_type) {
			
				add_filter('manage_edit-'.$post_type.'_columns', array(&$this, 'posts_columns'));
				$showing_thumbnail = TRUE;
				
			}
			
			if ($showing_thumbnail) {
				
				add_action('manage_pages_custom_column', array(&$this, 'posts_column'));
				add_action('manage_posts_custom_column', array(&$this, 'posts_column'));
				
				add_action('admin_head', array(&$this, 'admin_header_style'));
				
			}
		
		}
		
				
	}

	function posts_columns($post_columns) {
		
			global $post;
			
			$_post_columns = array();
			
			foreach ($post_columns as $k => $post_column) {
				
				if ( $k == "title" ) {
					
					$_post_columns['preview'] = _('Preview');
					
				}
				
				$_post_columns[$k] = $post_columns[$k];	
			}

			return $_post_columns;
	}
	
	function posts_column($name) {
	    
	    global $post;
	    switch ($name) {
	    	
	        case 'preview':
	        
	        	//Becco il thumb della prima immagine
	        	if (function_exists('get_post_thumbnail_id')) {
	        		$id_thumb = get_post_thumbnail_id($post->ID);
	        	} else {
	        		$id_thumb = null;
	        	}

				if ($post->post_parent) {

					_get_post_ancestors($post);
					
					$style = 'margin-left: ' .(count($post->ancestors) * 10) . 'px;';
					$child = 'child';
					$size = array(60,60);
				} else {
				
					$style = '';				
					$child = '';
					$size = array(80,80);
				}
	        	
	        	if ($id_thumb == null) {
	        		
					//Get first Attached Image
					$images = get_posts('post_parent='.$post->ID.'&post_type=attachment&post_mime_type=image&order=ASC&orderby=menu_order&posts_per_page=1');
					
					if ( !empty($images) ) {

						reset($images);
						$image = current($images);
						
						$image_id = $image->ID;
?>						
						<a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo  wp_get_attachment_image( $image_id , $size, null, array('style' => $style, 'class' => $child)); ?></a>
<?php						

					} else {
					
	?>
				<strong>No Image</strong>
	<?php			}        	
	        	} else {
	?>
		<a href="<?php echo get_edit_post_link($post->ID); ?>"><?php the_post_thumbnail( $size, array('style' => $style, 'class' => $child) ); ?></a>
	<?php
	     
	     	}
	            
	    }
	}
	
	function admin_header_style() {
	?>
	<style type="text/css">
	th#preview { width: 90px; }
		td.preview img {text-align: left; }		
		td.preview img.child {
			border-left: 4px solid #DDD;
			padding-left: 4px;			
		}
		td.column-preview {height: 80px; width: 90px; text-align: center;
		}
	</style>
	<?php
	}
	
	/**
	
		Admin Functions
		
	**/	

	function admin_menu() {
		add_options_page('Thumbnails for Backend', 'Thumbnails for Backend', 'edit_posts', basename(__FILE__), array(&$this, 'admin_page') );
	}
	
	function admin_page() {
	
		global $post;
		
		$msg = array();

		if (isset($_REQUEST['action'])) {			
			  	
			  	if (!isset($_REQUEST['create']) ) {
			  	
				  	switch($_REQUEST['action']) {
				  	
				  		case 'save':
				  		
			  				//Ritrovo i valori
			  				$tmp_options = unserialize( get_option('thumbfb_options') );
			  				
			  				$tmp_options["thumbfb_post_types"] = $_REQUEST['thumbfb_post_types'];
			  				
			  				update_option( 'thumbfb_options', serialize($tmp_options) );
			  				
			  				$msg[] = array(0, 'Options saved.');
				  		
				  		break;
				  		
				  	}
			  	
			  	}
			  	
		}

		$nonce = wp_create_nonce('thumbfb');
		$actionurl = $_SERVER['REQUEST_URI'];
		$plainurl = 'admin.php?page=adminthumbnails.php';
		
		$thumbfb_options = get_option('thumbfb_options');
	
		$thumbfb_options = is_string($thumbfb_options) ? unserialize( $thumbfb_options ) : $thumbfb_options;
			
		$custom_post_types = get_post_types(array(
				'public'   => true,
				'_builtin' => false
		
		));
		
		$custom_post_types[] = 'post';
		$custom_post_types[] = 'page';

?>

<div class="wrap">
	
	<div class="icon32" id="icon-options-general"><br/></div>
	<h2>Thumbnails for Backend</h2>
	
<?php if (!empty($msg) ) : foreach ($msg as $m) :?>

	<?php _e('<div id="message" class="'.($m[0] == 1 ? 'error' : 'updated' ).' fade"><p>' . $m[1] . '</p></div>'); ?>

<?php endforeach; endif; ?>	
	
	<form action="<?php echo $action_url; ?>" method="post">
		<input type="hidden" name="action" value="save" /> 
		<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce; ?>" />
	
		<h3>Options</h3>

	<table class="form-table">
		<tbody>
				
		<tr valign="top">
			<th scope="row"><label for="nf_category">Show thumbnails in</label></th>
			<td>
<?php 

			foreach ($custom_post_types as $cpt) : 
				//setup_postdata($post);
				$selected = '';
				
				if (is_array( $thumbfb_options["thumbfb_post_types"] )) {	
					$selected = ( in_array( $cpt, $thumbfb_options["thumbfb_post_types"] ) ? ' checked="checked" ' : '' ); 
				}
			
?>						
			<label for="thumbfb_post_types-<?php echo $cpt; ?>"><input type="checkbox" value="<?php echo $cpt; ?>" id="thumbfb_post_types-<?php echo $cpt; ?>" name="thumbfb_post_types[]" <?php echo $selected; ?>/> <?php echo $cpt; ?></label>

<?php 		endforeach; ?>			
			</td>
		</tr>	
		
		</tbody>
		
	</table>

		<p class="submit"><input type="submit" value="Save" class="button-primary" name="Submit"/></p>
	</form>
	
</div>

<?php

	}

}
if (is_admin()) {
	$thumbfb = new Thumbnailsforbackend();
	
	add_action('admin_menu', array(&$thumbfb, 'admin_menu'), 10);
	add_action('admin_init', array(&$thumbfb, 'add_thumbnails'), 10); //backwards compatible
}