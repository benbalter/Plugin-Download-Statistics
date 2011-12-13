<?php
$plugin_list = get_terms( 'pds_plugin', array( 'hide_empty' => false ) );
$plugins = array();
foreach ( $plugin_list as $plugin )
	$plugins[ $plugin->name ] = $plugin->slug;

$stats = new WP_Plugin_Download_Stats( $plugins );
$vars = array();
$graph = ( isset( $_GET['graph'] ) && $_GET['graph'] == 'daily' ) ? 'daily' : 'cumulative';
?>
<div class="wrap">
<h1>Plugin Download Statistics</h1>
<div id="chart" style="height: 350px; margin: 25px;"></div>
<?php foreach ( $plugins as $label=>$plugin ) {	?>
<div class="plugin">
	<h2><?php echo $label; ?></h2>
	<p><?php echo $stats->total_downloads( $plugin ); ?> (<?php echo number_format( ( $stats->total_downloads( $plugin, false ) / $stats->all_downloads() ) * 100, 2); ?>%)</p>
</div>
<?php } ?>
<div class="plugin">
<h2>Total Downloads</h2>
<p><strong><?php echo number_format( $stats->all_downloads() ); ?></strong></p>
</div>
<div style="clear:both;">&nbsp;</div>
<p>Graph: 
<?php if ( $graph == 'daily' ) { ?>
<strong>Daily</strong>
<?php } else { ?>
<a href="<?php echo esc_url( add_query_arg( 'graph', 'daily' ) ); ?>">Daily</a> 
<?php } ?>
<?php if ( $graph == 'cumulative' ) { ?>
<strong>Cumulative</strong>
<?php } else { ?>
<a href="<?php echo esc_url( remove_query_arg( 'graph' ) ); ?>">Cumulative</a> 
<?php } ?>
<p><a href="<?php echo admin_url( 'edit-tags.php?taxonomy=pds_plugin'); ?>">Manage Plugin List</a></p>

<script>
jQuery(document).ready(function($){
<?php foreach ( $plugins as $plugin ) { 
	$vars[ $plugin ] = $stats->js_safe_name( $plugin );
?>
	var <?php echo $vars[ $plugin ] ?> = [<?php foreach ( $stats->{'parse_' . $graph}( $plugin ) as $date => $downloads ) { ?>['<?php echo $date; ?> 12:00PM', <?php echo $downloads; ?>], <?php } ?> ];
<?php } ?>
  var combined = [<?php foreach ( $stats->{'all_' . $graph}() as $date => $downloads ) { ?>['<?php echo $date; ?> 12:00PM', <?php echo $downloads; ?>], <?php } ?> ];
  var plot1 = $.jqplot('chart', [<?php echo implode(',', $vars ); ?>,combined], {
    axes:{xaxis:{renderer:$.jqplot.DateAxisRenderer},yaxis:{min:0}},
    series:[<?php foreach ( $plugins as $label=>$plugin ) echo "{label:'" . $label ."'},"; ?> {label:'Total Downloads'}],
    legend:{show:true, location:'nw'},
    seriesDefaults:{lineWidth:3, showMarker:false},
  });
});
</script>
</div>