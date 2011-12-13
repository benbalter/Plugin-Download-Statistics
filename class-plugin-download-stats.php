<?php

/**
 * Class which provides download statistics for one or more WordPress plugins
 * Note: 
 * 		Daily = number of downloads of that plugin in a given day
 * 		Cumulative = number of downloads to date on that given day
 * 		Slug = Plugin's unique identifier from URL, e.g, 'wp-document-revisions'
 */
class WP_Plugin_Download_Stats {
	
	public $api_base = 'http://wordpress.org/extend/stats/plugin-xml.php';
	public $query_arg = 'slug';
	public $ttl = 3600; //1hr
	public $cache_group = 'wp_download_stats';
	public $slugs = array();
	public $data = array();
	
	/**
	 * Can init class with a string (single plugin slug) or an array of plugin slugs
	 */
	function __construct( $slug = null ) {

		$this->slugs = (array) $slug;

	}
	
	/**
	 * Verifies slug is passed to functions
	 */
	function get_slug( $slug = null ) {
		
		if ( $slug != null && $slug !== array() )
			return $slug;
		
		die( 'no slug' );
		
	}
	
	/**
	 * Calls wordpress.org API to fetch plugin download stats
	 */
	function fetch_data( $slug = null ) {
		
		$slug = $this->get_slug( $slug );
		
		$url = $this->build_url( $slug );
		
		if ( isset( $this->data[ $slug ] ) )
			return $this->data[ $slug ];
		
		if ( $cache = wp_cache_get( $slug, $this->cache_group ) )
			return $cache;
			
		$data = wp_remote_get( $url );
		
		if ( is_wp_error( $data ) )
			wp_die( $data-> get_error_message() );
			
		$data = simplexml_load_string( wp_remote_retrieve_body( $data ) );
		
		//parse the data into raw, daily and cumulative counts, store as public var
		$this->data[ $slug ]['raw'] = $data;
		$this->parse_daily( $slug );
		$this->parse_cumulative( $slug );
		
		//can't serialize simplexml obj
		unset( $this->data[ $slug ]['raw'] );

		wp_cache_set( $slug, $this->data[ $slug ], $this->cache_group, $this->ttl );
		
		return $this->data[ $slug ];
	
	}
	
	/**
	 * Adds slug to API URL
	 */
	function build_url( $slug = null ) {
		$slug = $this->get_slug( $slug );
		return add_query_arg( $this->query_arg, $slug, $this->api_base);
	}
	
	/**
	 * Take API data and parse into arrays of date => # downloads
	 */
	function parse_daily( $slug = null ) {
	
		$slug = $this->get_slug( $slug );
		$input = $this->fetch_data( $slug );
		$data = array();
		
		if ( isset( $input['daily'] ) )
			return $input['daily'];
			
		$output = array();
		
		foreach ( array( 'dates', 'downloads') as $id => $key ) 
			foreach ( $input['raw']->chart_data->row[ $id ] as $value ) 
				$data[ $key ][] = ( $id == 0 ) ? (string) $value : (int) $value;
			
		foreach ( $data['dates'] as $id => $date ) {

			if ( $id == 0 )
				continue;
				
			$output[$date] = $data['downloads'][$id];
		
		}
		
		$this->data[ $slug ]['daily'] = $output;
	
		return $output;	
	}
	
	/**
	 * Calculates a plugins total # of downloads
	 */
	function total_downloads( $slug = null, $number_format = true ) {			
		$slug = $this->get_slug( $slug );
		$data = $this->fetch_data( $slug );
		$total = array_sum( $data['daily'] );

		if ( !$number_format )
			return $total;
		
		return number_format( $total );
	}
	
	/**
	 * Parses daily data into cumulative counts of total downloads (not just # of downloads on that day)
	 */
	function parse_cumulative( $slug = null ) {
	
		$slug = $this->get_slug( $slug );
		$data = $this->fetch_data( $slug );
		
		if ( isset( $data['cumulative'] ) )
			return $data['cumulative'];
		
		$data = $data['daily'];	
		$keys = array_keys( $data );
		$i = 0;

		foreach ( $data as &$value ) {

			if ( $i == 0 ) {
				$i++;
				continue;
			}

			$value += $data[ $keys[ $i - 1 ] ];
			$i++;

		}
		
		$this->data[ $slug ]['cumulative'] = $data;
		
		return $data;
	}
	
	/**
	 * Combine multiple arrays of download counts into a single array of arrays
	 */
	function combine_counts( $plugins = array() ) {
			
		$output = array();
		foreach ( $plugins as $plugin )
			foreach( $plugin as $date => $downloads ) 
				$output[ $date ] = ( isset( $output[ $date ] ) ) ? $output[ $date ] + $downloads : $downloads;
			
		return $output;
	}
	
	/**
	 * Converts slug to Javascript safe name by removing hyphens
	 */
	function js_safe_name( $slug = null ) {
		$slug = $this->get_slug( $slug );
		$slug = str_replace( '-', '_', $slug );
		return $slug;
	}
	
	/**
	 * Total # of downloads for all plugins within class
	 */
	function all_downloads() {
		$total = 0;
		foreach ( $this->slugs as $slug )
			$total += $this->total_downloads( $slug, false );
			 
		return $total;
	}	
	
	/**
	 * Combined cumulative # of downloads by day
	 */
	function all_cumulative() {

		$cumulatives = array();
		foreach ( $this->slugs as $slug )
			$cumulatives[] = $this->parse_cumulative( $slug );
		
		return $this->combine_counts( $cumulatives );
		
	}
	
	/**
	 * Combined daily downloads by day
	 */
	function all_daily() {
	
		$dailies = array();
		foreach ( $this->slugs as $slug )
			$dailies[] = $this->parse_daily( $slug );
		
		return $this->combine_counts( $dailies );

	}
	
}
