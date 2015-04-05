<?php
/*
 * Plugin Name: CapitalP.org Quote Widget
 * Plugin URI: http://trepmal.com/2011/12/30/capitalp-org-wordpress-widget/
 * Description: Grab a quote from capitalp.org, display it.
 * Version: 0.4
 * TextDomain: capitalp-widget
 * Author: Kailey Lampert
 * Author URI: http://kaileylampert.com
 */

/**
 * The primary thing. Fetch a quote
 *
 * Cache quote for up to an hour for the sake of Jaquith's server
 * @return string Quote HTML
 */
function capitalp_get_quote() {

	$transient = 'capitalp_quote';

	// delete_transient( $transient );
	if ( false === ( $quote = get_transient( $transient ) ) ) {

		$r = wp_remote_get('http://capitalp.org');
		$b = $r['body'];

		$b = explode('<div>', $b );
		$b = explode('</div>', $b[1] );
		$quote = str_replace( '<p><img src=e.png>P</p>', 'P', $b[0] );

		set_transient( $transient, $quote, HOUR_IN_SECONDS );
	}

	return $quote;
}

/**
 * Shortcode callback
 */
function capitalp_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'cite' => true,
	), $atts );

	$raw_quote = capitalp_get_quote();

	$quote = "<blockquote><p>{$raw_quote}</p>" .
	( $atts['cite'] ? '<p>&mdash; <a href="http://capitalP.org/">capitalP.org</a></p>' : '' ) .
	'</blockquote>';

	return apply_filters( 'capitalp_quote', $quote, $raw_quote, $atts['cite'], 'shortcode' );

}
add_shortcode( 'capitalp', 'capitalp_shortcode' );

/**
 * the widget thing
 */
function register_capitalp_widget() {
	register_widget( 'CapitalP_Quote_Widget' );
}
add_action( 'widgets_init', 'register_capitalp_widget' );

/**
 * Widget
 */
class CapitalP_Quote_Widget extends WP_Widget {

	/**
	 */
	function __construct() {
		$widget_ops = array('classname' => 'capitalp-widget', 'description' => __( 'Retrieve quote from capitalp.org', 'capitalp-widget' ) );
		$control_ops = array( );
		parent::WP_Widget( 'capitalp', __( 'CapitalP.org Quote', 'capitalp-widget' ), $widget_ops, $control_ops );
	}

	/**
	 */
	function widget( $args, $instance ) {

		echo $args['before_widget'];
		echo $instance['hide_title'] ? '' : $args['before_title'] . $instance['title'] . $args['after_title'];

		$raw_quote = capitalp_get_quote();

		// invert this so it's more like shortcode
		$cite = ! $instance['hide_citation'];

		$quote = "<blockquote><p>{$raw_quote}</p>" .
		( $cite ? '<p>&mdash; <a href="http://capitalP.org/">capitalP.org</a></p>' : '' ) .
		'</blockquote>';

		echo apply_filters( 'capitalp_quote', $quote, $raw_quote, $cite, 'widget' );

		echo $args['after_widget'];

	}

	/**
	 */
	function update($new_instance, $old_instance) {

		$instance = $old_instance;
		$instance['title']         = esc_attr( $new_instance['title'] );
		$instance['hide_title']    = (bool) $new_instance['hide_title'] ? 1 : 0;
		$instance['hide_citation'] = (bool) $new_instance['hide_citation'] ? 1 : 0;
		return $instance;

	}

	/**
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
				'title'         => 'CapitalP.org Quote',
				'hide_title'    => 0,
				'hide_citation' => 0
			) );

		// $title, $hide_title, $hide_citation
		extract( $instance );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'capitalp-widget' );?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>
		<p style="width:50%;float:left;height:20px;">
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_title'); ?>" name="<?php echo $this->get_field_name('hide_title'); ?>"<?php checked( $hide_title ); ?> />
			<label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e( 'Hide Title?', 'capitalp-widget' );?></label>
		</p>
		<p style="width:50%;float:right;height:20px;">
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_citation'); ?>" name="<?php echo $this->get_field_name('hide_citation'); ?>"<?php checked( $hide_citation ); ?> />
			<label for="<?php echo $this->get_field_id('hide_citation'); ?>"><?php _e( 'Hide Citation?', 'capitalp-widget' );?></label>
		</p>
		<br style="clear:both;" />
		<?php
	}

}
