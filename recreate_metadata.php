<?php

/**
 * Recreate Masala Relevanssi metadata from command line
 *
 * Version: 2014-02-22
 */

require("../../../wp-load.php");

$args = array( 'post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => 'any', 'post_parent' => null ); 
$attachments = get_posts( $args );
if ( $attachments ) {
	foreach ( $attachments as $post ) {
		echo("#".$post->ID."\t".$post->post_title."\n");
		del_masala_content_metadata($post->ID);
		set_masala_content_metadata($post->ID);
		}
}

?>
