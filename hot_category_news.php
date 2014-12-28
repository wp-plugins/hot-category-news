<?php
/**
 * Plugin Name: Hot Category News
 * Plugin URI: http://hot-themes.com/wordpress/plugins/category-news
 * Description: Hot Category News will displays a selected number of posts from a specific category.
 * Version: 1.0
 * Author: HotThemes
 * Author URI: http://hot-themes.com/
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'hot_category_news_load_widgets' );
add_action('admin_init', 'hot_category_news_textdomain');
/**
 * Register our widget.
 * 'HotCategoryNews' is the widget class used below.
 *
 * @since 0.1
 */
function hot_category_news_load_widgets() {
	register_widget( 'CategoryNews' );
}

function hot_category_news_textdomain() {
	load_plugin_textdomain('hot_category_news', false, dirname(plugin_basename(__FILE__) ) . '/languages');
}
	
/**
 * CategoryNews Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */

class CategoryNews extends WP_Widget {
     
	/**
	 * Widget setup.
	 */
	 
	function CategoryNews() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'Hot_category_news', 'description' => __('Hot Category News', 'hot_category_news') );

		/* Widget control settings. */
		$control_ops = array(  'id_base' => 'hot-category-news' );

		/* Create the widget. */
		$this->WP_Widget( 'hot-category-news', __('Hot Category News', 'hot_category_news'), $widget_ops, $control_ops );

		add_action('wp_print_styles', array( $this, 'CategoryNews_styles'),12);
		add_action('admin_init', array( $this,'admin_utils'));
    }
	
	function admin_utils(){
		//wp_enqueue_style( 'hot-category-news-style', plugins_url('/admin.css', __FILE__));
	}

	function CategoryNews_styles(){
	   	wp_enqueue_style( 'hot-category-news-style', plugins_url('/style.css', __FILE__));
	}

	function GetDefaults()
	{
		return array( 
			'title' => ''
            ,'category'=>''
			,'count'=>'4'
            ,'cattitle' => 1		
            ,'show_date' => 0 					
            ,'orderby'=>'date'
			,'order'=>'DESC'
			,'separator_on_last' => 1
            ,'words' => 30    					
		);
	}
	
	/**
	 * How to display the widget on the screen.
	 */

	function widget( $args, $instance ) {
	    extract( $args );
	    $title = apply_filters( 'widget_title', $instance['title'] );
        echo $before_widget;

        if (!empty($title)) {
			echo $before_title . $title . $after_title;
		}

        $defaults = $this->GetDefaults();
		$instance = wp_parse_args( (array) $instance, $defaults );  
		
		if($instance['category']){

		//-------------------------RENDER START----------------------------------------------?>
		<div class="hot-category-news">
			<?php
				$post_slots = array();
				$args = array();
				
				$arg_arr = explode(',',$instance['category']);
				if(is_numeric($arg_arr[0]))
					$args['cat'] = $instance['category'];
				else
					$args['category_name'] = $instance['category'];
				
				$args['orderby'] = $instance['orderby'];
				$args['order'] = $instance['order'];
				$args['posts_per_page'] = $instance['count'];
				$args['paged'] = 1;
				
				$query = new WP_Query($args);
				$post_counter = 0;
				
				while($query->have_posts()){
				$query->the_post();
                   				
				$CONTENT = get_the_content('');
				 
				///-- POST RENDER START------------ 
				    if(!function_exists("cleanImgAttributes")){
						function cleanImgAttributes($string,$tag_array,$attr_array) {
							$attr = implode("|",$attr_array);
							foreach($tag_array as $tag) {
								$tag_regex = "/<($tag).+?>/";
								preg_match_all($tag_regex, $string, $matches, PREG_PATTERN_ORDER);
								foreach($matches as $match){
									$cleanedup = preg_replace('/('.$attr.')=([\\"\\\']).+?([\\"\\\'])/', "", $match[0]);
									$cleanedup = preg_replace('|  +|', ' ', $cleanedup);
									$cleanedup = str_replace(" >",">",$cleanedup);
									$string = str_replace($match[0],$cleanedup,$string);
								}
							}
							return $string;
						}
					}
		
					// Word limitation
					if (!function_exists('word_limiter')) {
						function word_limiter($str, $limit = 100, $end_char = ' ') {
							if (trim($str) == '')
								return $str;
							preg_match('/\s*(?:\S*\s*){'. (int) $limit .'}/', $str, $matches);
							if (strlen($matches[0]) == strlen($str))
								$end_char = '';
							return rtrim($matches[0]).$end_char;
						}
					}
					if ($instance['words']) {
						$CONTENT = word_limiter($CONTENT,(int)$instance['words']);
					}
		
					// HTML cleanup
					
					$CONTENT = strip_tags($CONTENT, "<br><br /><a><p><b><i><u><span><img>");
		
					/* Single item output inside - START HERE */

					// Post thumbnail
					if(get_the_post_thumbnail()) {
						$html = '<a href="'.get_permalink(get_the_ID()).'">';
						$html .= get_the_post_thumbnail(get_the_ID(), 'thumbnail');
						$html .= '</a>';
					}else{
						$html = '';
					}
				
					// Post title
					if ($instance['cattitle']) {
						$html .= '<h4 class="hot-category-news-title"><a href="'.get_permalink(get_the_ID()).'">';
						$html .= get_the_title();
						$html .= '</a></h4>
						';
					}
		
					// Post creation date
					if ($instance['show_date']) {	
						$html .= '<div class="hot-category-news-date">'.get_the_date(get_option('date_format').' '.get_option('time_format')).'</div>';
					}
		
					$html .= $CONTENT;
					
					$html .= ' <a class="more-link" href="'.get_permalink(get_the_ID()).'"><span>'.__('Read more').'</span></a>
					';
					
			        $post_slots[$post_counter] = $html;
					$post_counter++;
					if($post_counter > (int)$instance['count']) break;
				///--POST RENDER END ---------------
				}
				wp_reset_postdata();
				
				// source is content section/category/item
				$in = false;
				foreach ($post_slots as $key => $value) {
				    if($in) echo '<div class="post_separator"></div>
				    	';
					$in = true;
					echo $value;
				}
				
				if($instance['separator_on_last']){
				  echo '<div class="post_separator"></div>';
				}

            ?> 
</div>
<div class="clr"></div>
	   
	   
	   <?php //-------------------------RENDER END-------------------------------------------------?>
	   <?php 
	   }
	   echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
    	
		foreach($new_instance as $key => $option) {
			$instance[$key] = $new_instance[$key];
		} 
		
		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
	    $defaults = $this->GetDefaults();
		$instance = wp_parse_args( (array) $instance, $defaults );  ?>

		<!-- Hot Cat News: Options -->

		<p><?php _e( 'Title:','hot_category_news' ); ?><br/>
		<input  type="text" name="<?php echo $this->get_field_name( 'title' ); ?>"   id="<?php echo $this->get_field_id( 'title' ); ?>"  value="<?php echo $instance['title']; ?>" />
		</p>

		<p><?php _e('Category (ID or slug)','hot_category_news'); ?><br/>
		<input  type="text" name="<?php echo $this->get_field_name( 'category' ); ?>"   id="<?php echo $this->get_field_id( 'category' ); ?>"  value="<?php echo $instance['category']; ?>" /></p>

		<p><?php _e('Order By','hot_category_news'); ?><br/>
		<select class="select" id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>" >							
			<option value="date"  >
			<?php _e('Create Date', 'hot_category_news'); ?>
			</option>
		
			<option value="modified"  >
			<?php _e('Modified Date', 'hot_category_news'); ?>
			</option>
		
			<option value="title"  >
			<?php _e('Title', 'hot_category_news'); ?>
			</option>
		
			<option value="comment_count"  >
			<?php _e('Comment count', 'hot_category_news'); ?>
			</option>
		
			<option value="rand"  >
			<?php _e('Random', 'hot_category_news'); ?>
			</option>
			
			<option value="none"  >
			<?php _e('None', 'hot_category_news'); ?>
			</option>						
									
		</select></p>
		<script type="text/javascript">
			document.getElementById('<?php echo $this->get_field_id( 'orderby' ); ?>').value = "<?php echo $instance['orderby']; ?>";
		</script>

		<p><?php _e('Order','hot_category_news'); ?><br/>
		<select class="select" id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>" >
									
			<option value="DESC"  >
			<?php _e('Descending', 'hot_category_news'); ?>
			</option>
		
			<option value="ASC"  >
			<?php _e('Ascending', 'hot_category_news'); ?>
			</option>
									
		</select></p>
		<script type="text/javascript">
			document.getElementById('<?php echo $this->get_field_id( 'order' ); ?>').value = "<?php echo $instance['order']; ?>";
		</script>

		<p><?php _e('How many posts to take','hot_category_news'); ?><br/>
		<input type="text" name="<?php echo $this->get_field_name( 'count' ); ?>" id="<?php echo $this->get_field_id( 'count' ); ?>" value="<?php echo $instance['count']; ?>" class="numeric" /></p>

		<p><?php _e('Show title','hot_category_news'); ?><br/>
		<select class="select" id="<?php echo $this->get_field_id( 'cattitle' ); ?>" name="<?php echo $this->get_field_name( 'cattitle' ); ?>" >

		    <option value="0"  >
			<?php _e('No', 'hot_category_news'); ?>
			</option>
			
			<option value="1"  >
			<?php _e('Yes', 'hot_category_news'); ?>
			</option>

		</select></p>
		<script type="text/javascript">
			document.getElementById('<?php echo $this->get_field_id( 'cattitle' ); ?>').value = "<?php echo $instance['cattitle']; ?>";
		</script>

		<p><?php _e('Show date','hot_category_news'); ?><br/>
		<select class="select" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" >
									
			<option value="0"  >
			<?php _e('No', 'hot_category_news'); ?>
			</option>
		
			<option value="1"  >
			<?php _e('Yes', 'hot_category_news'); ?>
			</option>
									
		</select></p>
		<script type="text/javascript">
			document.getElementById('<?php echo $this->get_field_id( 'show_date' ); ?>').value = "<?php echo $instance['show_date']; ?>";
		</script>

		<p><?php _e('Word limit','hot_category_news'); ?><br/>
		<input  type="text" name="<?php echo $this->get_field_name( 'words' ); ?>"   id="<?php echo $this->get_field_id( 'words' ); ?>"  value="<?php echo $instance['words']; ?>"  /></p>

		<p><?php _e('Show last separator','hot_category_news'); ?><br/>
		<select class="select" id="<?php echo $this->get_field_id( 'separator_on_last' ); ?>" name="<?php echo $this->get_field_name( 'separator_on_last' ); ?>" >
									
			<option value="0"  >
			<?php _e('No', 'hot_category_news'); ?>
			</option>
		
			<option value="1"  >
			<?php _e('Yes', 'hot_category_news'); ?>
			</option>
									
		</select></p>
		<script type="text/javascript">
			document.getElementById('<?php echo $this->get_field_id( 'separator_on_last' ); ?>').value = "<?php echo $instance['separator_on_last']; ?>";
		</script>		
		
	<?php  
	}
}

?>