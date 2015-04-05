<?php
/*
Plugin Name: CapitalP.org Quote Widget
Plugin URI: http://trepmal.com/2011/12/30/capitalp-org-wordpress-widget/
Description: Grab a quote from capitalp.org, display it.
Version: 0.3
Author: Kailey Lampert
Author URI: http://kaileylampert.com
*/

//yes, I should add docs
//no, I haven't added docs

//the primary thing
function get_capitalp_quote( $markup = true, $cite = true ) {
	$r = wp_remote_get('http://capitalp.org');
	$b = $r['body'];
	$b = explode('<div>', $b );
	$b = explode('</div>', $b[1] );
	$b = str_replace( '<p><img src=e.png>P</p>', 'P', $b[0] );

	$c = '<cite><a href="http://capitalP.org/">capitalP.org</a></cite>';

	if ( ! $markup )
		return $b;
	if ( ! $cite )
		return "<blockquote><p>{$b}</p></blockquote>";

	return "<blockquote><p>{$b}</p><p>{$c}</p></blockquote>";
}

//the shortcode thing
add_shortcode( 'capitalp', 'capitalp_shortcode' );
function capitalp_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'markup' => true,
		'cite' => true,
		'id' => '',
	), $atts ) );

	$_markup = $markup ? 'true' : 'false';
	$_cite = $cite ? 'true' : 'false';
	$id .= $_markup.$_cite;

 	$transient = substr( 'capitalp_shortcode-'.$id, 0, 40 );

	if ( false === ( $quote = get_transient( $transient ) ) ) {
		$quote = get_capitalp_quote();
		if ( ! $markup ) $quote = get_capitalp_quote( false );
		if ( ! $cite ) $quote = get_capitalp_quote( true, false );
		set_transient( $transient, $quote, 60*15 ); //15 minutes
	}


	return $quote;
}

//the widget thing
add_action( 'widgets_init', 'register_capitalp_widget' );
function register_capitalp_widget() {
	register_widget( 'CapitalP_Quote_Widget' );
}
class CapitalP_Quote_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'capitalp-widget', 'description' => __( 'Retrieve quote from capitalp.org' ) );
		$control_ops = array( );
		parent::WP_Widget( 'capitalp', __( 'CapitalP.org Quote' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		echo $before_widget;

		echo $instance['hide_title'] ? '' : $before_title . $instance['title'] . $after_title;
		
		$transient = $args['widget_id'] .'_content';

		if ( false === ( $quote = get_transient( $transient ) ) ) {
			$quote = get_capitalp_quote( false );
			set_transient( $transient, $quote, 60*15 ); //15 minutes
		}

		echo $quote;

		echo $instance['hide_citation'] ? '' : '<p>&mdash; <a href="http://capitalP.org/">capitalP.org</a></p>';

		echo $after_widget;

	} //end widget()

	function update($new_instance, $old_instance) {

		$instance = $old_instance;
		$instance['title'] = esc_attr( $new_instance['title'] );
		$instance['hide_title'] = (bool) $new_instance['hide_title'] ? 1 : 0;
		$instance['hide_citation'] = (bool) $new_instance['hide_citation'] ? 1 : 0;
		return $instance;

	} //end update()

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'CapitalP.org Quote', 'hide_title' => 0, 'hide_citation' => 0 ) );
		extract( $instance );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' );?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>
		<p style="width:50%;float:left;height:20px;">
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_title'); ?>" name="<?php echo $this->get_field_name('hide_title'); ?>"<?php checked( $hide_title ); ?> />
			<label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide Title?' );?></label>
		</p>
		<p style="width:50%;float:right;height:20px;">
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_citation'); ?>" name="<?php echo $this->get_field_name('hide_citation'); ?>"<?php checked( $hide_citation ); ?> />
			<label for="<?php echo $this->get_field_id('hide_citation'); ?>"><?php _e('Hide Citation?' );?></label>
		</p>
		<br style="clear:both;" />
		<?php
	} //end form()

}