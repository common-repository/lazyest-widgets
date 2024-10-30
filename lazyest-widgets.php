<?php
/*
Plugin Name: Lazyest Widgets
Plugin URI: http://brimosoft.nl/lazyest/gallery/widgets/
Description: Widget pack for Lazyest Gallery 
Date: 2012, November
Author: Brimosoft
Author URI: http://brimosoft.nl
Version: 0.6.0
License: GNU GPLv2
*/

/**
 * Widgets for Lazyest Gallery
 * 
 * @package Lazyest Gallery
 * @subpackage Lazyest Widgets
 * @version 0.6
 * @author Marcel Brinkkemper (lazyest@brimosoft.nl)
 * @copyright 2008-2011 Marcel Brinkkemper 
 * @license GNU GPL
 * @link http://brimosoft.nl/lazyest/gallery/widgets/
 * @todo random images from (recent) folders with click to folder
 */

/**
 * LG_Widget_Recent_Images
 *
 * @access public
 * @since 0.4
 */
class LG_Widget_Recent_Images extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_lazyest_last_image', 'description' => __( "The most recent images in your gallery") );
		parent::__construct('lazyest_last_image', __('LG Recent Images'), $widget_ops);
		add_action( 'wp_ajax_nopriv_lg_recent_image', array( &$this, 'get_image' ) );		
		add_action( 'wp_ajax_lg_recent_image', array( &$this, 'get_image' ) );		
	}
	
	/**
	 * LG_Widget_Recent_Images::widget()
	 * 
	 * @uses LazyestGallery::get_option 
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $lg_gallery;
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Images', 'lazyest-widgets') : $instance['title'], $instance, $this->id_base);
		if ( ! $number = absint( $instance['number'] ) )
 			$number = 4;
		$images = array();
		$recent_id = absint( $lg_gallery->get_option( 'image_indexing' ) );
		$number = min( $recent_id, $number );
				
		if ( 0 < $number ) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul class="lazyest_recent_list">
		<?php  for ( $recent = 0; $recent < $number; $recent++ ) :  ?>
		<li class="lazyest_recent" id="recent_<?php echo $recent ?>" style="display: none;"></li>
		<?php endfor; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		endif;
	}
	
	/**
	 * LG_Widget_Recent_Images::update()
	 * 
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}
	
	/**
	 * LG_Widget_Recent_Images::form()
	 * 
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 4;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lazyest-widgets' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of images to show:', 'lazyest-widgets' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
	
	/**
	 * LG_Widget_Recent_Images::get_image()
	 * Response to AJAX request for latest image
	 * 
	 * $_POST['recent'] int nth latest image
	 * 
	 * @return string html for thumbnail
	 */
	function get_image() {
		global $post, $lg_gallery;
		
		function get_image_( $recent ) {
			global $lg_gallery;
			$found = 0;
			$try = 0;
			$filevars = false;
			$not_images = get_option( 'lazyest_not_images' );
			if ( ! $not_images ) 
				$not_images = array();				
			$image_index = absint( $lg_gallery->get_option( 'image_indexing' ) );
			while ( ( $found <= $recent ) && ( $try < $image_index ) ) {
				$id = $image_index - $try;
				if ( ! in_array( $id, $not_images ) ) {
					$filevars = $lg_gallery->get_file_by_id( $id );
					if ( $filevars && $lg_gallery->is_image( $filevars[0] ) ) {
						$result = $filevars[0];
						$found++;
					} else {
						$not_images[] = $id;
					}
				}
				if ( $try < $image_index )
					$try++;
				else {
					$result = false;
					break;
				}	
			}
			update_option( 'lazyest_not_images', $not_images );
			return $result;
		}
		
		
		$recent = isset( $_POST['recent'] ) ? absint( $_POST['recent'] ) : -1;
		$nonce = $_POST['_wpnonce'];
  	if ( ! wp_verify_nonce( $nonce, 'lazyest_widgets' ) || -1 == $recent )
  		die(0);   		
		$response = '0';
		if ( ( -1 != $recent ) && ( $recent <= absint( $lg_gallery->get_option( 'image_indexing' ) ) ) ) {
			$filevar = get_image_( $recent );
			if ( $filevar ) {
				$folder_path = dirname( $filevar );
				$image_file = basename( $filevar );
				$folder = new LazyestFolder( $folder_path );
				if ( $folder ) {
					$image = $folder->single_image( $image_file, 'thumbs' );
					$response = '<div class="lg_thumb">'; 
					$onclick = $image->on_click();
			    $class= 'thumb';
			    if ( 'TRUE' != $lg_gallery->get_option( 'enable_cache' )  || 
						( ( 'TRUE' == $lg_gallery->get_option( 'async_cache' ) ) 
							&& ! file_exists( $image->loc() ) ) ) {
						$class .= ' lg_ajax';	
					}	
					$postid = is_object ( $post ) ? $post->ID : $lg_gallery->get_option( 'gallery_id' ); 
			    $response .= sprintf( '<div class="lg_thumb_image"><a id="%s_%s" href="%s" class="%s" rel="%s" title="%s" ><img class="%s" src="%s" alt="%s" /></a></div>',          
			      $onclick['id'],
			      $postid,
			      $onclick['href'],
			      $onclick['class'],
			      $onclick['rel'],
			      $image->title(),
			      $class,
			      $image->src(),
			      $image->alt()  
			    );             
    			if ( '-1' != $lg_gallery->get_option( 'captions_length' ) )	{			  
          	$thumb_caption = '<div class="lg_thumb_caption">';
				    $caption = $image->caption(); 
						$max_length = (int) $lg_gallery->get_option( 'captions_length' );
						if ( '0' != $lg_gallery->get_option( 'captions_length' ) )  {
							if ( strlen( $caption ) > $max_length ) {
							  strip_tags( $caption );
								$caption = substr( $caption, 0, $max_length - 1 ) . '&hellip;';
							}	
						}
				    $thumb_caption .= sprintf( '<span title="%s" >%s</span>',
				      $image->title(),
				      lg_html( $caption ) 
				    );  		
				    $thumb_caption .= '</div>';
					  
				    if ( ( 'TRUE' == $lg_gallery->get_option( 'thumb_description' ) ) ) {
				    	if ( '' != $image->description )
					      $thumb_caption .= sprintf( '<div class="thumb_description"><p>%s</p></div>',
					        lg_html( $image->description() )
					      );
				      $thumb_caption .= apply_filters( 'lazyest_thumb_description', '', $image );
				    }
						$response .= $thumb_caption;        		
					}	
        	$response .= apply_filters( 'lazyest_frontend_thumbnail', '', $image );
        	$response .= "</div>\n";	
				}
			}	
		}
		echo $response;
		die();
	}	
} // LG_Widget_Recent_Images

/**
 * LG_Widget_Random_Images
 *  
 * @since 0.4
 * @access public
 */
class LG_Widget_Random_Images extends WP_Widget {
	
	var $expiration;
	var $retry;
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_lazyest_random_image', 'description' => __( "Really random images from your gallery") );
		parent::__construct('lazyest_random_image', __('LG Really Random Images'), $widget_ops);
		add_action( 'wp_ajax_nopriv_lg_random_image', array( &$this, 'get_image' ) );		
		add_action( 'wp_ajax_lg_random_image', array( &$this, 'get_image' ) );
		$this->expiration = apply_filters( 'lazyest_widget_random_refresh', 10 );
		$this->retry = apply_filters( 'lazyest_widget_random_retry', 10 );		
	}
	
	/**
	 * LG_Widget_Random_Images::widget()
	 * 
	 * @uses LazyestGallery::get_option 
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $lg_gallery;
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Really Random Images', 'lazyest-widgets') : $instance['title'], $instance, $this->id_base);
		if ( ! $number = absint( $instance['number'] ) )
 			$number = 4;
		$images = array();
		$recent_id = absint( $lg_gallery->get_option( 'image_indexing' ) );
		$number = min( $recent_id, $number );
						
		if ( 0 < $number ) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul class="lazyest_random_list">
		<?php  for ( $random = 0; $random < $number; $random++ ) :  ?>
		<li class="lazyest_random" id="random_<?php echo $random ?>" style="display: none;"></li>
		<?php endfor; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		endif;
	}
	
	/**
	 * LG_Widget_Random_Images::update()
	 * 
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}
	
	/**
	 * LG_Widget_Random_Images::form()
	 * 
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 4;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lazyest-widgets' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of images to show:', 'lazyest-widgets' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
	
	/**
	 * LG_Widget_Random_Images::get_image()
	 * 
	 * Response to AJAX request for random image
	 * 
	 * $_POST['random'] int nth random image
	 * A random image is stored and will be used for subsequent requests until it expires
	 * 
	 * @return string html for thumbnail
	 */
	function get_image() {
		global $post, $lg_gallery;
				
		$random = isset( $_POST['random'] ) ? absint( $_POST['random'] ) : -1;
		$nonce = $_POST['_wpnonce'];
  	if ( ! wp_verify_nonce( $nonce, 'lazyest_widgets' ) || -1 == $random )
  		die(0);   	
		$not_images = get_option( 'lazyest_not_images' );
		if ( ! $not_images ) 
			$not_images = array();		
		$response = '0';
		if ( ( -1 != $random ) && ( $random <= absint( $lg_gallery->get_option( 'image_indexing' ) ) ) ) {
			if ( $buffer = get_transient( "lg_random_image_$random" ) ) {
				$filevar = $buffer;
			}	else {
				$filevar = false;
				$count = 0;			
				$tried = array();	
				
				while( ! $filevar && $count < $this->retry ) {
					$id = rand( 1, absint( $lg_gallery->get_option( 'image_indexing' ) ) );
					if ( in_array( $id, $tried ) || in_array( $id, $not_images ) )
						continue;
					$afile = $lg_gallery->get_file_by_id( $id );
					if ( $afile && $lg_gallery->is_image( $afile[0] ) ) {
						$filevar = $afile[0];
					} else {					
						$not_images[] = $id;
					}
					$tried[] = $id;
					$count++;
				}
				set_transient( "lg_random_image_$random", $filevar, $this->expiration * 60 );
			}
			update_option( 'lazyest_not_images', $not_images );
			if ( $filevar ) {
				$folder_path = dirname( $filevar );
				$image_file = basename( $filevar );
				$folder = new LazyestFolder( $folder_path );
				if ( $folder ) {
					$image = $folder->single_image( $image_file, 'thumbs' );
					$response = '<div class="lg_thumb">';      
        						$onclick = $image->on_click();
			    $class= 'thumb';
			    if ( 'TRUE' != $lg_gallery->get_option( 'enable_cache' )  || 
						( ( 'TRUE' == $lg_gallery->get_option( 'async_cache' ) ) 
							&& ! file_exists( $image->loc() ) ) ) {
						$class .= ' lg_ajax';	
					}	
					$postid = is_object ( $post ) ? $post->ID : $lg_gallery->get_option( 'gallery_id' ); 
			    $response .= sprintf( '<div class="lg_thumb_image"><a id="%s_%s" href="%s" class="%s" rel="%s" title="%s" ><img class="%s" src="%s" alt="%s" /></a></div>',          
			      $onclick['id'],
			      $postid,
			      $onclick['href'],
			      $onclick['class'],
			      $onclick['rel'],
			      $image->title(),
			      $class,
			      $image->src(),
			      $image->alt()  
			    );             
    			if ( '-1' != $lg_gallery->get_option( 'captions_length' ) )	{			  
          	$thumb_caption = '<div class="lg_thumb_caption">';
				    $caption = $image->caption(); 
						$max_length = (int) $lg_gallery->get_option( 'captions_length' );
						if ( '0' != $lg_gallery->get_option( 'captions_length' ) )  {
							if ( strlen( $caption ) > $max_length ) {
							  strip_tags( $caption );
								$caption = substr( $caption, 0, $max_length - 1 ) . '&hellip;';
							}	
						}
				    $thumb_caption .= sprintf( '<span title="%s" >%s</span>',
				      $image->title(),
				      lg_html( $caption ) 
				    );  		
				    $thumb_caption .= '</div>';
					  
				    if ( ( 'TRUE' == $lg_gallery->get_option( 'thumb_description' ) ) ) {
				    	if ( '' != $image->description )
					      $thumb_caption .= sprintf( '<div class="thumb_description"><p>%s</p></div>',
					        lg_html( $image->description() )
					      );
				      $thumb_caption .= apply_filters( 'lazyest_thumb_description', '', $image );
				    }
						$response .= $thumb_caption;        		
					}				
        	$response .= apply_filters( 'lazyest_frontend_thumbnail', '', $image );
        	$response .= "</div>\n";	
				}
			}	
		}
		echo $response;
		die();
	}	
	
} // LG_Widget_Random_Images

/**
 * LG_Widget_Random_Slideshow
 * 
 * @since 0.4
 * @access public
 */
class LG_Widget_Random_Slideshow extends WP_Widget {
	
	var $interval;
	var $retry;
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_lazyest_random_slideshow', 'description' => __( "A slideshow with really random images from your gallery") );
		parent::__construct('lazyest_random_slideshow', __('LG Really Random Images Slideshow'), $widget_ops);
		add_action( 'wp_ajax_nopriv_lg_random_slideshow', array( &$this, 'get_image' ) );		
		add_action( 'wp_ajax_lg_random_slideshow', array( &$this, 'get_image' ) );
		$this->retry = apply_filters( 'lazyest_widget_random_retry', 10 );
	}
	
	/**
	 * LG_Widget_Random_Slideshow::widget()
	 * 
	 * @uses LazyestGallery::get_option 
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $lg_gallery;	
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Really Random Slideshow', 'lazyest-widgets') : $instance['title'], $instance, $this->id_base);
		$height = $lg_gallery->get_option('thumbheight');
		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title; 
?>		
		<div class="lazyest_random_slidehow">		
			<div class="lazyest_random_slideshow_item" id="lazyest_random_slideshow_<?php echo $this->number; ?>" style="height:<?php echo $height ?>px">
				<div class="lg_thumb active">
					<div class="lg_thumb_image">
						<img class="thumb" src="<?php echo admin_url( 'images/wpspin_light.gif') ?>" alt="" />
					</div>
					<div class="lg_thumb_caption">
						<span><?php esc_html_e( 'Loading...', 'lazyest_widgets' ); ?></span>
					</div>			
				</div>
				<div class="lg_thumb"></div>
				<div class="lg_thumb"></div>
			</div>
		</div>
		<script type='text/javascript'>var widget_lazyest_random_slideshow = <?php echo intval( $lg_gallery->get_option( 'slide_show_duration' ) ) * 1000 ?></script> 
<?php 
		echo $after_widget; 
	}
	
	/**
	 * LG_Widget_Random_Slideshow::update()
	 * 
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}	
	
	/**
	 * LG_Widget_Random_Slideshow::form()
	 * 
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$interval = isset( $instance['interval'] ) ? absint( $instance['interval'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lazyest-widgets' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>				
<?php
	}
	
	
	/**
	 * LG_Widget_Random_Slideshow::get_image()
	 * 
	 * @return void
	 */
	function get_image() {
		global $post, $lg_gallery;			
					
		$nonce = $_POST['_wpnonce'];
  	if ( ! wp_verify_nonce( $nonce, 'lazyest_widgets' ) )
  		die(0);   	
		
		$not_images = get_option( 'lazyest_not_images' );
		if ( ! $not_images ) 
			$not_images = array();
					
		$response = '0';		
		$filevar = false;
		$count = 0;			
		$tried = array();		
		while( ! $filevar && $count < $this->retry ) {
			$id = rand( 1, absint( $lg_gallery->get_option( 'image_indexing' ) ) );
			if ( in_array( $id, $tried ) || in_array( $id, $not_images ) )
				continue;
			$afile = $lg_gallery->get_file_by_id( $id );
			if ( $afile && $lg_gallery->is_image( $afile[0] ) ) {
				$filevar = $afile[0];
			} else {					
				$not_images[] = $id;
			}
			$tried[] = $id;
			$count++;
		}
			
		update_option( 'lazyest_not_images', $not_images );
		if ( $filevar ) {
			$folder_path = dirname( $filevar );
			$image_file = basename( $filevar );
			$folder = new LazyestFolder( $folder_path );
			if ( $folder ) {
				$image = $folder->single_image( $image_file, 'thumbs' );
				$response = '<div class="lg_thumb">'; $onclick = $image->on_click();
		    $class= 'thumb';
		    if ( 'TRUE' != $lg_gallery->get_option( 'enable_cache' )  || 
					( ( 'TRUE' == $lg_gallery->get_option( 'async_cache' ) ) 
						&& ! file_exists( $image->loc() ) ) ) {
					$class .= ' lg_ajax';	
				}	
				$postid = is_object ( $post ) ? $post->ID : $lg_gallery->get_option( 'gallery_id' ); 
		    $response .= sprintf( '<div class="lg_thumb_image"><a id="%s_%s" href="%s" class="%s" rel="%s" title="%s" ><img class="%s" src="%s" alt="%s" /></a></div>',          
		      $onclick['id'],
		      $postid,
		      $onclick['href'],
		      $onclick['class'],
		      $onclick['rel'],
		      $image->title(),
		      $class,
		      $image->src(),
		      $image->alt()  
		    );    
      	$response .= "</div>\n";
			}
		}	
		echo $response;
		die();
	}			
} //LG_Widget_Random_Slideshow


/**
 * LG_Widget_Recent_Slideshow
 * 
 * @since 0.4
 * @access public
 */
class LG_Widget_Recent_Slideshow extends WP_Widget {
	
	var $latest;
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_lazyest_recent_slideshow', 'description' => __( "A slideshow for your latest images in your gallery") );
		parent::__construct('lazyest_recent_slideshow', __('LG Recent Images Slideshow'), $widget_ops);
		add_action( 'wp_ajax_nopriv_lg_recent_slideshow', array( &$this, 'get_image' ) );		
		add_action( 'wp_ajax_lg_recent_slideshow', array( &$this, 'get_image' ) );
	}
	
	/**
	 * LG_Widget_Recent_Slideshow::widget()
	 * Uses non-compliant atribute for div: recent
	 * 
	 * @uses LazyestGallery::get_option 
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $lg_gallery;	
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Image Slideshow', 'lazyest-widgets') : $instance['title'], $instance, $this->id_base);
		if ( ! $latest = absint( $instance['latest'] ) ) $latest = 5;
		$height = $lg_gallery->get_option('thumbheight');
		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title; 
?>		
		<div class="lazyest_recent_slidehow">		
			<div class="lazyest_recent_slideshow_item" id="lazyest_recent_slideshow_<?php echo $this->number; ?>" style="height:<?php echo $height ?>px">
				<span class="args" id="latest_<?php echo $this->number; ?>"><?php echo $latest ?></span><span class="args" id="recent_<?php echo $this->number; ?>">0</span>
				<div class="lg_thumb active">
					<div class="lg_thumb_image">
						<img class="thumb" src="<?php echo admin_url( 'images/wpspin_light.gif') ?>" alt="" />
					</div>
					<div class="lg_thumb_caption">
						<span><?php esc_html_e( 'Loading...', 'lazyest_widgets' ); ?></span>
					</div>			
				</div>
				<div class="lg_thumb"></div>
				<div class="lg_thumb"></div>
			</div>
		</div>
<?php 
		echo $after_widget; 
	}
	
	/**
	 * LG_Widget_Recent_Slideshow::form()
	 * 
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$interval = isset( $instance['interval'] ) ? absint( $instance['interval'] ) : 5;
		$latest = isset( $instance['latest'] ) ? absint( $instance['latest'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lazyest-widgets' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
			
		<p><label for="<?php echo $this->get_field_id( 'latest' ); ?>"><?php _e( 'Show latest:', 'lazyest-widgets' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'latest' ); ?>" name="<?php echo $this->get_field_name( 'latest' ); ?>" type="text" value="<?php echo $latest; ?>" size="3" /></p>					
<?php
	}
	
	/**
	 * LG_Widget_Recent_Slideshow::update()
	 * 
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['latest'] = (int) $new_instance['latest'];
		return $instance;
	}	
	
	/**
	 * LG_Widget_Recent_Slideshow::get_image()
	 * 
	 * @return void;
	 */
	function get_image() {
		
		global $post, $lg_gallery;
		
		function get_image_( $recent ) {
			global $lg_gallery;
			$found = 0;
			$try = 0;
			$filevars = false;
			$not_images = get_option( 'lazyest_not_images' );
			if ( ! $not_images ) 
				$not_images = array();				
			$image_index = absint( $lg_gallery->get_option( 'image_indexing' ) );
			while ( ( $found <= $recent ) && ( $try < $image_index ) ) {
				$id = $image_index - $try;
				if ( ! in_array( $id, $not_images ) ) {
					$filevars = $lg_gallery->get_file_by_id( $id );
					if ( $filevars && $lg_gallery->is_image( $filevars[0] ) ) {
						$result = $filevars[0];
						$found++;
					} else {
						$not_images[] = $id;
					}
				}
				if ( $try < $image_index )
					$try++;
				else {
					$result = false;
					break;
				}	
			}
			update_option( 'lazyest_not_images', $not_images );
			return $result;
		}
					
		$nonce = $_POST['_wpnonce'];
  	if ( ! wp_verify_nonce( $nonce, 'lazyest_widgets' ) )
  		die(0);   	
				
		$response = '0';		
		$filevar = false;
		$count = 0;			
		$latest = min( intval( $_POST['latest'] ), $lg_gallery->get_option( 'image_indexing' ) );
		$recent = max( 1, intval( $_POST['recent'] ) );
		$tried = array();	
		
		$filevar = get_image_( $recent );
		if ( $filevar ) {
			$folder_path = dirname( $filevar );
			$image_file = basename( $filevar );
			$folder = new LazyestFolder( $folder_path );
			if ( $folder ) {
				$image = $folder->single_image( $image_file, 'thumbs' );
				$response = '<div class="lg_thumb">';$onclick = $image->on_click();
		    $class= 'thumb';
		    if ( 'TRUE' != $lg_gallery->get_option( 'enable_cache' )  || 
					( ( 'TRUE' == $lg_gallery->get_option( 'async_cache' ) ) 
						&& ! file_exists( $image->loc() ) ) ) {
					$class .= ' lg_ajax';	
				}	
				$postid = is_object ( $post ) ? $post->ID : $lg_gallery->get_option( 'gallery_id' ); 
		    $response .= sprintf( '<div class="lg_thumb_image"><a id="%s_%s" href="%s" class="%s" rel="%s" title="%s" ><img class="%s" src="%s" alt="%s" /></a></div>',          
		      $onclick['id'],
		      $postid,
		      $onclick['href'],
		      $onclick['class'],
		      $onclick['rel'],
		      $image->title(),
		      $class,
		      $image->src(),
		      $image->alt()  
		    );    
      	$response .= "</div>\n";	
			}
		}	
		echo $response;
		die();
	}			
	 
} // LG_Widget_Recent_Slideshow

/**
 * LG_Widget_Recent_Folders
 *
 * @access public
 * @since 0.6
 */
class LG_Widget_Recent_Folders extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_lazyest_last_folder', 'description' => __( "The most recent folders in your gallery") );
		parent::__construct('lazyest_last_folder', __('LG Recent Folders'), $widget_ops);
	}
	
	/**
	 * LG_Widget_Recent_Folders::widget()
	 * 
	 * @uses LazyestGallery::get_option 
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $lg_gallery;
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Folders', 'lazyest-widgets') : $instance['title'], $instance, $this->id_base);
		if ( ! $number = absint( $instance['number'] ) )
 			$number = 4;
		$images = array();
		$folders = $this->get_folders();
		$number = min( count( $folders ), $number );
		$recent = 0;
		if ( 0 < $number ) {
			$itemwidth = floor(90/$number);
			echo "
			$before_widget";
			if ( $title ) echo $before_title . $title . $after_title;
			echo"		
				<style type='text/css'>
					.lgf-item.recent-folder{
						width:{$itemwidth}%;						
					}
					.lgf-item.recent-folder .lg_thumb {
						max-width: 90%;
						padding: 2%;
						margin: 0;
					}
				</style>
				<div class='lg_gallery'>
					<div class='folders'>
						<div class='dir_view'>
							<ul class='lgf-list recent-folders'>";
							foreach( $folders as $folder ) {
								echo "
									<li class='lgf-item recent-folder' id='recent-folder_".  $recent . "'>
										<div class='lg_thumb'>
										";
										$this->recent_folder_icon( $folder );
										echo "
										</div>
									</li>";
							$recent++; 
							if ( $recent == $number ) 
								break;
			}
			echo "
							</ul>
						</div>
					</div>
				</div>
			";
			echo $after_widget;
		}
	}
	
	/**
	 * LG_Widget_Recent_Folders::update()
	 * 
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}
	
	/**
	 * LG_Widget_Recent_Folders::form()
	 * 
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 4;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lazyest-widgets' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of folder icons to show:', 'lazyest-widgets' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
	
	/**
	 * LG_Widget_Recent_Folders::get_folders()
	 * Get an array of folders containing images
	 * 
	 * @return array of LazyestFolder
	 */
	function get_folders() {
		global $lg_gallery;
		$option = $lg_gallery->get_option( 'sort_alphabetically' );
		$lg_gallery->change_option( 'sort_alphabetically', 'DFALSE' );
		$folders = $lg_gallery->folders( 'subfolders', 'visible' );
		$lg_gallery->change_option( 'sort_alphabetically', $option );
		if ( count( $folders ) ) {
			foreach ( $folders as $key => $folder ) {
				if ( 0 == $folder->count( 'root' ) ) {
					unset( $folders[$key] );
				}
			}
		}
		return $folders;
	}
	
	/**
	 * LG_Widget_Recent_Folders::recent_folder_icon()
	 * Output a folder icon and caption
	 * 
	 * @param LazyestFolder $folder
	 * @return void
	 */
	function recent_folder_icon( $folder ) {
		$folder->load();
		echo $folder->icon_div() . $folder->caption_div();
	}
	
} // LG_Widget_Recent_Folders


/**
 * lazyest_widgets_script()
 * enqueue scripts for lazyest-widgets
 * 
 * @return void
 */
function lazyest_widgets_script() {
	wp_enqueue_script( 'lazyest_widgets', plugins_url( 'js/ajax.js',  __FILE__ ), array( 'jquery' ), '0.1', true );
 	wp_localize_script( 'lazyest_widgets', 'lazyest_widgets', lazyest_widgets_localize() );
}

/**
 * lazyest_widgets_localize()
 * localize lazyest-widget scripts
 * 
 * @return array
 */
function lazyest_widgets_localize() {
	global $lg_gallery;
	return array( '_nonce'  => wp_create_nonce( 'lazyest_widgets' ),
      					'ajaxurl' => admin_url( 'admin-ajax.php' ),
								'slideshow_duration' => apply_filters( 'lazyest_widgets_slideshow_duration', 1000 * intval( $lg_gallery->get_option( 'slide_show_duration' ) ) ) );
}

function lazyest_widgets_style() {
	wp_enqueue_style( 'lazyest_widgets', plugins_url( 'css/lazyest-widgets.css',  __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'lazyest_widgets_script', 1 );
add_action( 'wp_print_styles', 'lazyest_widgets_style', 1 );

function lazyest_widgets_init() {
	register_widget( 'LG_Widget_Recent_Images' );
	register_widget( 'LG_Widget_Random_Images' );
	register_widget( 'LG_Widget_Random_Slideshow' );	
	register_widget( 'LG_Widget_Recent_Slideshow' );
	register_widget( 'LG_Widget_Recent_Folders' );
}

add_action( 'widgets_init', 'lazyest_widgets_init', 1 );
?>