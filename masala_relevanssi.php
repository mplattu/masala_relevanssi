<?php
/*
Plugin Name: Masala Relevanssi
Plugin URI: https://github.com/mplattu/masala_relevanssi/
Description: Adds metadata of configured file types to Relevanssi search engine in order to allow search inside attachments
Version: 2014-02-22
Author: Matti Lattu, Alex Nano
License: GPL2
*/

/*  Copyright 2063 Alex Nano  (email : nanodust@gmail.com)
    Copyright 2014 Matti Lattu
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// This should really be in config GUI - but now it is here

$MASALA_RELEVANSSI_HELPERS = Array(
	'pdf' => '/usr/bin/pdftotext "%f" -',	// Debian: poppler-utils
	'doc' => '/usr/bin/catdoc -a "%f"',	// Debian: catdoc
	'docx' => '/usr/bin/docx2txt "%f" -',	// Debian: docx2txt
	// 'xls' => '/usr/bin/xls2csv -q0 "%f"',
	//'xls' => '/usr/bin/ssconvert -T Gnumeric_stf:stf_csv "%f" fd://1',	// Debian: gnumeric
	//'xlsx' => '/usr/bin/ssconvert -T Gnumeric_stf:stf_csv "%f" fd://1',	// Debian: gnumeric
	'ppt' => '/usr/bin/catppt "%f"',	// Debian: catdoc
	'pptx' => '/usr/local/bin/pptx2txt.pl "%f" -',
	'odt' => '/usr/bin/odt2txt --width=-1 "%f"',	// Debian: odt2txt
	'ods' => '/usr/bin/ods2txt --width=-1 "%f"',	// Debian: odt2txt
	'odp' => '/usr/bin/odp2txt --width=-1 "%f"',	// Debian: odt2txt
);

// Primary WordPress action triggers for adding Masala metadata

add_action("add_attachment", "set_masala_content_metadata");
add_action("delete_attachment", "del_masala_content_metadata");

// WordPress action triggers to make Relevanssi to use Masala metadata

add_filter('relevanssi_content_to_index', 'add_masala_extra_content', 10, 2);
add_filter('relevanssi_excerpt_content', 'add_masala_extra_content', 10, 2);

/// Helper functions

// log function - from http://fuelyourcoding.com/simple-debugging-with-wordpress/

if(!function_exists('_log')){
  function _log( $message ) {
   if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

/// Core functions

function set_masala_content_metadata($post_id){
	global $MASALA_RELEVANSSI_HELPERS;
	
	_log('starting to analyze attachment for post # '.$post_id);

	// get attachment URL from postID
	$metadata = get_post($post_id, ARRAY_A);
	
	$ext = strtolower(pathinfo($metadata['guid'], PATHINFO_EXTENSION));
	_log('attachment extention is "'.$ext.'"');
	if (!array_key_exists($ext,$MASALA_RELEVANSSI_HELPERS)) {
		_log('Unknown extension, giving up');
		return true;
	} else {
		_log('Extension has following helper: '.$MASALA_RELEVANSSI_HELPERS[$ext]);
	}
	
	// it's an allowed extension, let's continue processing... 

	// get absolute path
	$upload_data = wp_upload_dir();

	if (!preg_match('/\/wp-content\/uploads\/(.+)$/', $metadata['guid'], $matches)) {
		// No match for whatever reason
		_log('Attachment URL '.$metadata['guid'].' did not contain understandable path');
		return true;
	}
	
	$absPath = $upload_data['basedir'].'/'.$matches[1];

	if (!is_file($absPath) and !is_readable($absPath)) {
		_log("File $absPath is not regular file or the file is not readable");
		return true;
	}
	
	// Get command from $MASALA_RELEVANSSI_HELPERS
	$command = $MASALA_RELEVANSSI_HELPERS[$ext];
	
	// Replace %f with filename
	$command = preg_replace('/\%f/', $absPath, $command);
	
	_log('executing command '.$command);
	
	// Prepare to run $command
	
	$descriptorspec = array(
	   0 => array("pipe", "r"),  
	   1 => array("pipe", "w"), 
	   2 => array("pipe", "w")
	);

	$process = proc_open($command, $descriptorspec, $pipes);
	$fileContents = "";
	while (!feof($pipes[1])) {
		$fileContents.=fgets($pipes[1], 1024);
	}
   
	fclose($pipes[0]);
	fclose($pipes[1]);
   fclose($pipes[2]);
   	
   // should really do some error handling here if it's not zero.
   $return_value = proc_close($process);

	if ($return_value != 0) {
		error_log("Executing following command returned error code #".$return_value.": ".$command);
		return true;
	}
	
	_log('command resulted: '.$fileContents);

	// Filter $fileContents
	// Filter unwanted characters
	$fileContents = preg_replace('/[\x00-\x1f]|[\x21-\x2f]|[\x3a-\x40]/', ' ', $fileContents);
	$fileContents = preg_replace('/\s+/', ' ', $fileContents);
	
	// set custom metadata
	add_post_meta( $post_id, "masala_relevanssi_content", $fileContents);
}


function del_masala_content_metadata($post_id){

	global $wpdb;
	
	_log('deleting content metadata for '.$post_id);
	
	// Remove custom metadata
	if (delete_post_meta( $post_id, "masala_relevanssi_content")) {
		_log("Deleted content metadata for ".$post_id);
	} else {
		_log("Could not delete content metadata for ".$post_id);
	}
}


function add_masala_extra_content ($content, $post) {
	$meta = get_post_meta($post->ID, "masala_relevanssi_content", true);
	if ($meta != '') {
		$content .= $meta;
	}
	return $content;
}

// options menu

add_action( 'admin_menu', 'my_plugin_menu' );

// Add a new submenu under Settings:
function my_plugin_menu() {
 add_options_page(__('Masala Relevanssi','menu-test'), __('Masala Relevanssi','menu-test'), 'manage_options', 'masalarelevanssi-settings', 'masala_relevanssi_settings_page');

}

// masala_relevanssi_settings_page() displays the page content for the Test settings submenu
function masala_relevanssi_settings_page() {
	global $MASALA_RELEVANSSI_HELPERS;

	echo("<h2>" . __( 'Masala Relevanssi Settings', 'menu-test' ) . "</h2>");

	echo("<p>Current settings support following file types:</p>");
	echo("<ul>\n");

	foreach ($MASALA_RELEVANSSI_HELPERS as $this_ext => $this_helper) {
		echo('<li><strong>'.$this_ext.'</strong> <code>'.$this_helper.'</code>');

		$helper_arr = explode(' ', $this_helper);

		if (!is_executable($helper_arr[0])) {
			echo(' <strong>Warning: '.$helper_arr[0].' is not executable</strong>');
		}
		echo('</li>');
	}
	echo("</ul>\n");

}


?>
