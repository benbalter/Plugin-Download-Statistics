<?php
/*
Plugin Name: Plugin Download Stats
Description: Tracks Plugin Downloads
Version: 1.0
Author: Benjamin J. Balter
Author URI: http://ben.balter.com/
License: GPL2
*/

add_action( 'admin_enqueue_scripts', 'pds_enqueue' );
add_action( 'admin_menu', 'pds_register_menu' );
include( 'class-plugin-download-stats.php' );

function pds_settings_page() {
	include( 'template.php' );
}

function pds_register_menu() {
	add_submenu_page( 'index.php', 'Plugin Download Statistics', 'Plugin Stats', 'manage_options', 'plugin_download_statistics', 'pds_settings_page' );
}

function pds_enqueue( $hook ) {

	if ( $hook != 'dashboard_page_plugin_download_statistics' )
		return;

	wp_enqueue_style( 'jqplot', plugins_url( '/js/jquery.jqplot.min.css', __FILE__ ) );
	wp_enqueue_script( 'jqplot', plugins_url( '/js/jquery.jqplot.min.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'jqplot-axis-renderer', plugins_url( '/js/jqplot.dateAxisRenderer.min.js', __FILE__ ) );
	
	add_action( 'admin_head', 'pds_ie_js' );	
	add_action( 'admin_head', 'pds_css' );	

}

function pds_ie_js() { ?>
	
	<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php plugins_url( '/js/excanvas.js', __FILE__ ); ?>"></script><![endif]-->
	
	<?php
}

function pds_css() { ?>
<style>.plugin {float:left; width:450px; text-align: center;}</style>
<?php }

function pds_register_taxonomy() {

    $labels = array( 
        'name' => _x( 'Plugins', 'plugin' ),
        'singular_name' => _x( 'Plugin', 'plugin' ),
        'search_items' => _x( 'Search Plugins', 'plugin' ),
        'popular_items' => _x( 'Popular Plugins', 'plugin' ),
        'all_items' => _x( 'All Plugins', 'plugin' ),
        'parent_item' => _x( 'Parent Plugin', 'plugin' ),
        'parent_item_colon' => _x( 'Parent Plugin:', 'plugin' ),
        'edit_item' => _x( 'Edit Plugin', 'plugin' ),
        'update_item' => _x( 'Update Plugin', 'plugin' ),
        'add_new_item' => _x( 'Add New Plugin', 'plugin' ),
        'new_item_name' => _x( 'New Plugin Name', 'plugin' ),
        'separate_items_with_commas' => _x( 'Separate plugins with commas', 'plugin' ),
        'add_or_remove_items' => _x( 'Add or remove plugins', 'plugin' ),
        'choose_from_most_used' => _x( 'Choose from the most used plugins', 'plugin' ),
        'menu_name' => _x( 'Plugins', 'plugin' ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => false,
        'show_in_nav_menus' => false,
        'show_ui' => false,
        'show_tagcloud' => false,
        'hierarchical' => false,

        'rewrite' => false,
        'query_var' => false
    );

    register_taxonomy( 'pds_plugin', null, $args );
}

add_action( 'init', 'pds_register_taxonomy' );

function pds_add_form_help() { ?>
<div style="float:right">
	<h3>Plugin Download Statistics Help</h3>
	<P><strong>Name:</strong> The name of the plugin</P>
	<P><strong>Slug:</strong> The plugin's unique slug, taken from the URL<br />
	<em>E.g.,</em> if the plugin's URL is <code>http://wordpress.org/extend/plugins/wp-document-revisions/</code>, the slug would be <code>wp-document-revisions</code></P>
	<p><a href="<?php echo admin_url( 'plugins.php?page=plugin_download_statistics' ); ?>">Back to Plugin Download Statistics</a></p>
</div>
<?php }

add_action( 'pds_plugin_add_form', 'pds_add_form_help' );
