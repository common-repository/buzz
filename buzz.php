<?php
/*
Plugin Name:  Google Buzz
Plugin URI: http://www.vjcatkick.com/?page_id=16113
Description: Display Google Buzz Timeline at sidebar
Version: 0.0.1
Author: V.J.Catkick
Author URI: http://www.vjcatkick.com/
*/

/*
License: GPL
Compatibility: WordPress 2.6 with Widget-plugin.

Installation:
Place the widget_single_photo folder in your /wp-content/plugins/ directory
and activate through the administration panel, and then go to the widget panel and
drag it to where you would like to have it!
*/

/*  Copyright V.J.Catkick - http://www.vjcatkick.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/* Changelog
* Feb 14 2010 - v0.0.1
- Initial release
*/

/*
*/





add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 300;' ) );

function widget_google_buzz_get_rss_feed_items( $url, $count ) {
	@include_once( ABSPATH . WPINC . '/feed.php' );

	$options = get_option( 'widget_google_buzz_wp' );
	$timezonestr = $options[ 'widget_google_buzz_wp_timezone' ];
	if( !$timezonestr ) $timezonestr = 'Asia/Tokyo';
	date_default_timezone_set( $timezonestr );

	$rss = fetch_feed( $url );
	if( is_wp_error( $rss ) ) {
		echo 'server: error [ fetch_feed ].';

		return( false );
	}else{
		$maxitems = $rss->get_item_quantity( $count );
		$rss_items = $rss->get_items( 0, $maxitems );
	} /* if else */

	return( $rss_items );
} /* widget_google_buzz_get_rss_feed_items() */

function widget_google_buzz_get_prof_icon_and_desc( $rss_url, $imgsize ) {
	$result = '';

	$prof_url = str_replace( 'http://buzz.googleapis.com/feeds/', 'http://www.google.com/profiles/', $rss_url );
	$prof_url = str_replace( '/public/posted', '', $prof_url );
	$filedata = file_get_contents( $prof_url );

	$result_img_str = '';
	$result_desc_str = '';
	if( $filedata ) {
		preg_match( '/ll_profilephoto(.+?)alt=/is', $filedata, $matches );
		$result_img_str = str_replace( 'll_profilephoto photo" src="', "", $matches[ 0 ] );
		$result_img_str = str_replace( '" alt=', '', $result_img_str );
		$result_img_str = 'http://www.google.com' . $result_img_str;

		preg_match( '/<div id=\"about_box(.+)?<dl>/is', $filedata, $matches );
		$result_desc_str = strip_tags( $matches[0] , '<br>' );
	}else{
		 $result_img_str = get_bloginfo( 'wpurl' ) . '/wp-content/plugins/buzz/images/buzz_icon.png';
	} /* if else */

	/* maybe this func returns only image url, not full html */
	$result = '<img class="" src="' . $result_img_str . '" border="0" style="float: left; margin-right: 6px; width: ' . $imgsize . 'px; height: ' . $imgsize . 'px;" />';

	$retv = array();
	$retv[ 'icon' ] = $result;
	$retv[ 'desc' ] = $result_desc_str;

	return( $retv );
} /* widget_google_buzz_get_prof_icon_and_desc() */

function widget_google_buzz_wp_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_google_buzz_wp( $args ) {
		extract( $args );

		$options = get_option( 'widget_google_buzz_wp' );

		$widget_google_buzz_wp_title = $options[ 'widget_google_buzz_wp_title' ];
		$widget_google_buzz_wp_rss_url = $options[ 'widget_google_buzz_wp_rss_url' ];
		$widget_google_buzz_wp_numitem = $options[ 'widget_google_buzz_wp_numitem' ];
		$widget_google_buzz_wp_width_item = $options[ 'widget_google_buzz_wp_width_item' ];
		$widget_google_buzz_wp_timestamp = $options[ 'widget_google_buzz_wp_timestamp' ];
		$widget_google_buzz_wp_disptime = $options[ 'widget_google_buzz_wp_disptime' ];

		$output = '<div id="widget_google_buzz_wp"><ul>';

//		$theUrl = 'http://buzz.googleapis.com/feeds/109431645852091896195/public/posted';

		$theUrl = $widget_google_buzz_wp_rss_url;
		$num_items = $widget_google_buzz_wp_numitem;
		$items = widget_google_buzz_get_rss_feed_items( $theUrl, $num_items );

		$author_name = '';

		foreach( $items as $item ) {
			if( !$author_name ) {
				$tmp_author = $item->get_author( 0 );
				$author_name = $tmp_author->get_name();
				$author_prof_link = $tmp_author->get_link();
				$icon_and_desc = widget_google_buzz_get_prof_icon_and_desc( $theUrl, 48 );

				$output .= '<div class="" style="width: 100%; float: right;" >';

				$output .= '<div class="" style="width: 100%; float: left; " >';
				$output .= $icon_and_desc[ 'icon' ];
				$output .= '<span style="font-size: 1.2em; font-weight: bold; " >';
				$output .= '<a href="' . $author_prof_link . '" target="_blank" >' . $author_name . '</a><br />';
				$output .= '</span><span style="font-size: 0.9em; font-weight: normal; " >';
				$output .= $icon_and_desc[ 'desc' ];
				$output .= '</span>';
				$output .= '</div>';

				$output .= '<div class="" style="width: 100%; float: left; height: 0.5em; border-bottom: 1px solid #DDD; margin-bottom: 0.8em; " ></div>';

				$output .= '</div>';
			} /* if */

			$item_desc = $item->get_description();
			$output .= '<li style="float: right; width: 100%; text-align: left; " >';

			$item_desc = str_replace( '<div>', '<div class="google_buzz_item" style="float: right; width: ' . $widget_google_buzz_wp_width_item . ';" >', $item_desc );
			$output .= $item_desc;


			/******
			the way to get item count, array-array-array... need to find much smarter way.
			*******/
			$n_count = $item->{'data'}['child']['http://www.w3.org/2005/Atom']['link'][1]['attribs']['http://purl.org/syndication/thread/1.0']['count'];
			$output .= '<div class="google_buzz_item_date" style="width: 100%; text-align: right; font-size: 0.9em;">';
			$output .= '<a href="' . $item->get_permalink() . '" target="_blank" >';
			if( $n_count ) {
				$output .= $n_count . ' comment';
				if( $n_count > 1 ) $output .= 's';
			} /* if */

			if( $widget_google_buzz_wp_timestamp ) {
				if( $n_count ) $output .= ', ';
				$output .= $item->get_date('m/d/Y');
				if( $widget_google_buzz_wp_disptime ) $output .= $item->get_date(' H:i');
			} /* if */
			$output .= '</a>';
			$output .= '</div>';

			$output .= '</li>';
		} /* foreach */

		// These lines generate the output
		$output .= '</ul></div>';
		$output .= '<div style="width: 100%; float: left; height: 1.0em;" ></div>';

		echo $before_widget . $before_title . $widget_google_buzz_wp_title . $after_title;
		echo $output;
		echo $after_widget;
	} /* widget_google_buzz_wp() */

	function widget_google_buzz_wp_control() {
		$options = $newoptions = get_option('widget_google_buzz_wp');
		if ( $_POST["widget_google_buzz_wp_submit"] ) {
			$newoptions['widget_google_buzz_wp_title'] = htmlspecialchars( $_POST[ 'widget_google_buzz_wp_title' ] );
			$newoptions[ 'widget_google_buzz_wp_rss_url' ] = htmlspecialchars( $_POST[ 'widget_google_buzz_wp_rss_url' ] );
			$newoptions[ 'widget_google_buzz_wp_numitem' ] = (int)$_POST[ 'widget_google_buzz_wp_numitem' ];
			$newoptions[ 'widget_google_buzz_wp_width_item' ] = htmlspecialchars( $_POST[ 'widget_google_buzz_wp_width_item' ] );
			$newoptions[ 'widget_google_buzz_wp_timestamp' ] = (int)$_POST[ 'widget_google_buzz_wp_timestamp' ];
			$newoptions[ 'widget_google_buzz_wp_disptime' ] = (int)$_POST[ 'widget_google_buzz_wp_disptime' ];
			$newoptions['widget_google_buzz_wp_timezone'] = htmlspecialchars( $_POST[ 'widget_google_buzz_wp_timezone' ] );
		} /* if */
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_google_buzz_wp', $options);
		} /* if */

		// those are default value
		if ( !$options['widget_google_buzz_wp_numitem'] ) $options['widget_google_buzz_wp_numitem'] = 5;
		if ( !$options['widget_google_buzz_wp_width_item'] ) $options['widget_google_buzz_wp_width_item'] = '100%';
		if ( $options['widget_google_buzz_wp_timestamp'] === false ) $options['widget_google_buzz_wp_timestamp'] = '1';
		if ( $options['widget_google_buzz_wp_disptime'] === false ) $options['widget_google_buzz_wp_disptime'] = '1';
		if ( !$options['widget_google_buzz_wp_timezone'] ) $options['widget_google_buzz_wp_timezone'] = 'Asia/Tokyo';

		$widget_google_buzz_wp_rss_url = $options[ 'widget_google_buzz_wp_rss_url' ];
		$widget_google_buzz_wp_numitem = $options[ 'widget_google_buzz_wp_numitem' ];
		$widget_google_buzz_wp_width_item = $options[ 'widget_google_buzz_wp_width_item' ];
		$widget_google_buzz_wp_timestamp = $options[ 'widget_google_buzz_wp_timestamp' ];
		$widget_google_buzz_wp_disptime = $options[ 'widget_google_buzz_wp_disptime' ];
		$widget_google_buzz_wp_timezone = $options[ 'widget_google_buzz_wp_timezone' ];

		$widget_google_buzz_wp_title = htmlspecialchars($options['widget_google_buzz_wp_title'], ENT_QUOTES);
?>

	    <?php _e('Title:'); ?> <input style="width: 170px;" id="widget_google_buzz_wp_title" name="widget_google_buzz_wp_title" type="text" value="<?php echo $widget_google_buzz_wp_title; ?>" /><br />

	    <?php _e('RSS URL:'); ?> <input style="width: 170px;" id="widget_google_buzz_wp_rss_url" name="widget_google_buzz_wp_rss_url" type="text" value="<?php echo $widget_google_buzz_wp_rss_url; ?>" /><br />*Buzz URL is at Google Profile page.<br />

	    <?php _e('Number item to display:'); ?> <input style="width: 70px;" id="widget_google_buzz_wp_numitem" name="widget_google_buzz_wp_numitem" type="text" value="<?php echo $widget_google_buzz_wp_numitem; ?>" /><br />

	    <?php _e('Item width:'); ?> <input style="width: 170px;" id="widget_google_buzz_wp_width_item" name="widget_google_buzz_wp_width_item" type="text" value="<?php echo $widget_google_buzz_wp_width_item; ?>" /><br />

        <input id="widget_google_buzz_wp_timestamp" name="widget_google_buzz_wp_timestamp" type="checkbox" value="1" <?php if( $widget_google_buzz_wp_timestamp ) { echo "checked";}  ?>/> Timestamp<br />

		&nbsp;&nbsp;<input id="widget_google_buzz_wp_disptime" name="widget_google_buzz_wp_disptime" type="checkbox" value="1" <?php if( $widget_google_buzz_wp_disptime ) { echo "checked"; } ?>/> Display time<br />

<!--
<input id="" name="" type="checkbox" value="1" <?php if( $xxx ) { echo "checked"; } ?>/><br />
-->

	    <?php _e('TimeZone:'); ?> <input style="width: 170px;" id="widget_google_buzz_wp_timezone" name="widget_google_buzz_wp_timezone" type="text" value="<?php echo $widget_google_buzz_wp_timezone; ?>" /><br />


  	    <input type="hidden" id="widget_google_buzz_wp_submit" name="widget_google_buzz_wp_submit" value="1" />

<?php
	} /* widget_google_buzz_wp_control() */

	register_sidebar_widget('Google Buzz', 'widget_google_buzz_wp');
	register_widget_control('Google Buzz', 'widget_google_buzz_wp_control' );
} /* widget_google_buzz_wp_init() */

add_action('plugins_loaded', 'widget_google_buzz_wp_init');

?>