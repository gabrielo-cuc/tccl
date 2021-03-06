<?php
namespace CCL\Staff;

/**
 * Setup actions and filters for staff
 *
 * @return void
 */
function setup() {
	$n = function ( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init', $n( 'register_staff_post_type' ) );

	add_action( 'admin_enqueue_scripts', $n( 'load_admin_script' ), 20 );

	add_action( 'admin_menu', $n( 'import_page' ) );

	add_action( 'wp_ajax_retrieve_staff', __NAMESPACE__ . '\\retrieve_staff' );

	add_action( 'add_meta_boxes_staff', $n('add_staff_meta_box' ) );


}

/**
 * Register the 'staff' post type using Extended CPTs
 *
 * This can be replaced with a standard post type instead of using a library if desired
 *
 * See https://github.com/johnbillion/extended-cpts for more information
 * on registering post types with the extended-cpts library.
 */
function register_staff_post_type() {

	register_extended_post_type( 'staff', array(
		'menu_icon'       => 'dashicons-id',
		'supports'        => array( 'title' ), // content, editor, thumbnail would allow content to be edited
		'capability_type' => 'post',
		'capabilities'    => array(
			'create_posts' => 'do_not_allow', // Remove support for "Add New" (can also change to a role, rather than false)
		),
		'public'				=> false,
		'exclude_from_search'	=> false,
		'publicly_queryable'	=> true,
		'show_ui'				=> true,		
		'has_archive' => false,
		'map_meta_cap'    => true, // Allows created posts to be edited
		'admin_cols' => array(
			'role' => array(
				'taxonomy' => 'staff_role'
			),
			'subject' => array(
				'taxonomy' => 'subject'
			),
		),
	),

		array(

			# Override the base names used for labels:
			'singular'  => 'Staff Member',
			'plural'    => 'Staff',
			'slug'      => 'staff'

		)
	);

}


/**
 * Add option page to the Staff menu
 */
function import_page() {
	// add top level menu page
	add_submenu_page(
		'edit.php?post_type=staff',
		'Staff Import',
		'Import',
		'publish_posts',
		'staff_import',
		'\CCL\Staff\import_page_html'
	);
}

/**
 * Staff import page callback
 */
function import_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'publish_posts' ) ) {
		return;
	}

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<h2>Import</h2>

		<p>Use this tool to import and update staff from staff.</p>

		<?php if ( ! \CCL\Integrations\LibGuides\get_libguide_key() || ! \CCL\Integrations\LibGuides\get_libguide_id() ) : ?>
			<div class="error notice">
				<p><strong>LibGuide API key and id have not been set</strong></p>
			</div>
		<?php endif; ?>

		<?php // Currently imported stats, api query for recently updated? ?>

		<div id="staff-importer">
			<div id="staff-info" class="hidden">

				<div class="spinner is-active" style="float:none;width:auto;height:auto;padding:10px 0 10px 40px;background-position:0;">
					Importing staff members from Springshare&hellip;
				</div>

				<div id="staff-import-response" class="staff-response"></div>

			</div>

			<input type="hidden" name="staff-import-nonce" id="staff-import-nonce" value="<?php echo wp_create_nonce( 'staff_import_nonce' ); ?>" />

			<p class="submit">
				<input type="submit" name="staff-import" id="staff-import" class="button button-primary" value="Import from Springshare">
			</p>

		</div>

	</div>
	<?php
}

/**
 * Add scripts and styles for import page
 */
function load_admin_script() {
	// Enqueue Styles

	// Enqueue Scripts
	wp_enqueue_script( 'staff', get_template_directory_uri() . '/assets/js/admin/staff.js', 'jquery', '1.0', true );

}

/**
 * AJAX function for retrieving staff from Springshare
 */
function retrieve_staff() {
	check_ajax_referer( 'staff_import_nonce', 'staff_nonce' );

	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_die( 'Error: invalid permissions' );
	}

	$staff = process_staff();

	$response = '<h3>Results</h3>';

	// @todo sort out actual error response here
	if ( $staff == 'error' ) {
		$response .= '<p>Error</p>';
	} else {
		$response .= '<ul>';
		$response .= '<li><strong>Retrieved:</strong> ' . $staff['retrieved'] . ' staff</li>';
		$response .= '<li><strong>Imported:</strong> ' . $staff['added'] . '</li>';
		$response .= '<li><strong>Updated:</strong> ' . $staff['updated'] . '</li>';
		$response .= '<li><strong>Deleted:</strong> ' . $staff['deleted'] . '</li>';		
		$response .= '</ul>';
	}

	wp_die( $response );
}

/**
 * Retrieve staff members, process them and return results
 *
 * @return array Data indicating success or failure of the import
 */
function process_staff() {

	//convert json object into array
	$members = json_decode( json_encode( \CCL\Integrations\Libguides\get_all_staff() ), true );
	
	//compare two data fields and delete entries that are no longer in the API.
	//not - insert array for API data, however this functio will convert to array
	$deleted_from_WP = \CCL\Helpers\check_import_for_deletions( 'member_id', 'staff', $members );  

	$results    			= array();
	$results['retrieved']	= count( $members );
	$results['added']   	= 0;
	$results['updated'] 	= 0;
	$results['deleted']		= $deleted_from_WP;

	foreach ( $members as $member ) {
		$add_member = add_staff_member( $member );

		if ( 'added' == $add_member ) {
			$results['added'] = $results['added'] + 1;
		} elseif ( 'updated' == $add_member ) {
			$results['updated'] = $results['updated'] + 1;
		} else {
			//
		}
	}

	return $results;
}


/**
 * Create or update staff member
 *
 * @param $member
 *
 * @return string added|updated
 */
function add_staff_member( $member ) {

	// quick and dirty way to convert multi-dimensional object to array
	//$member = json_decode( json_encode( $member ), true );

	// check against custom id field to see if post already exists
	$member_id = $member['id'];

	// meta query to check if the member id already exists
	$duplicate_check = new \WP_Query( array(
		'post_type'  => 'staff',
		'meta_query' => array(
			array(
				'key'   => 'member_id',
				'value' => $member_id
			)
		),
	) );

	/*
	 * Construct arguments to use for wp_insert_post()
	 */
	$args = array();
	$post_updated = false;	

	if ( $duplicate_check->have_posts() ) {

		$duplicate_check->the_post();
		$args['ID'] = get_the_ID(); // existing post ID (otherwise will insert new)
		
		//don't know why but it's easier to set a varibale here if we are dealing with an update
		$post_updated = true;

	}

	$args['post_title'] = $member['first_name'] . " " . $member['last_name']; // post_title
	// $args['post_content'] = $member['description']; // post_content
	$args['post_status'] = 'publish'; // default is draft
	$args['post_type'] = 'staff';

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$args['post_status'] = 'publish'; // automatically publish posts on servers with debug enabled
	}

	/*
	 * Create the Staff Member
	 */
	$member_post_id = wp_insert_post( $args );

	// Insert data into custom fields
	update_post_meta( $member_post_id, 'member_id', $member['id']); // custom field ->
	update_post_meta( $member_post_id, 'member_first_name', $member['first_name'] ); // custom field ->
	update_post_meta( $member_post_id, 'member_last_name', $member['last_name'] ); // custom field ->
	update_post_meta( $member_post_id, 'member_email', $member['email'] ); // custom field ->
	update_post_meta( $member_post_id, 'member_image', $member['profile']['image']['url'] ); // custom field ->
	update_post_meta( $member_post_id, 'member_friendly_url', $member['profile']['url'] ); // custom field ->

	// Raw data for development
	update_post_meta( $member_post_id, 'member_raw_data', $member );
	
	//check first and add title if exists, not all titles are set
	if( array_key_exists( 'title', (array) $member['profile']['box'] ) ){
		update_post_meta( $member_post_id, 'member_title', $member['profile']['box']['title'] );
		
	}

	// Add Subjects to custom taxonomy
	if ( array_key_exists( 'subjects', $member ) ) {
		$subjects_array = array();
		foreach ( $member['subjects'] as $subject ) {
			$subjects_array[] = $subject['name'];
		}
		// Add subject name to subject taxonomy
		wp_set_object_terms( $member_post_id, $subjects_array, 'subject' );
	}

	if ( $post_updated ) {
		return "updated";
	} else {
		return "added";
	}

}

/**
 * Create a metabox to display data retrieved from the API
 *
 * @param $post
 */
function add_staff_meta_box( $post ) {
	add_meta_box(
		'api_data_meta_box',
		__( 'Data from LibGuides' ),
		__NAMESPACE__ . '\\render_staff_data_metabox',
		'staff',
		'normal',
		'high'
	);
}

/**
 * Render the API data metabox
 */
function render_staff_data_metabox() {
	global $post;

	echo '<p>';

	echo '<strong>Member ID:</strong> ' . get_post_meta( $post->ID, 'member_id', true ) . '<br>';
	echo '<strong>First Name:</strong> ' . get_post_meta( $post->ID, 'member_first_name', true ) . '<br>';
	echo '<strong>Last Name:</strong> ' . get_post_meta( $post->ID, 'member_last_name', true ) . '<br>';
	echo '<strong>Title:</strong> ' . get_post_meta( $post->ID, 'member_title', true ) . '<br>';
	echo '<strong>Email:</strong> ' . get_post_meta( $post->ID, 'member_email', true ) . '<br>';
	echo '<strong>Profile URL:</strong> ' . get_post_meta( $post->ID, 'member_friendly_url', true ) . '<br>';

	echo '</p>';

	$image = get_post_meta( $post->ID, 'member_image', true );
	if ( $image ) {
		echo '<strong>Image URL:</strong> ' . $image . '<br>';
		echo '<img src="' . $image . '" style="width:200px;"> <br>';
	}

	echo '<hr>';

	$raw_data = get_post_meta( $post->ID, 'member_raw_data', true );

	echo '<strong>Raw Data</strong> (<span id="raw-data-toggle" style="color:#21759B;cursor:pointer">show</span>)';

	echo '<div id="raw-api-data" class="hidden">';

	echo '<p><em>Currently crossed with expand: <code>profile</code> and <code>subjects</code>. No other options.</em></p>';

	echo '<div style="white-space:pre-wrap;font-family:monospace;">';
	print_r( $raw_data );
	echo '</pre>';

	echo '</div>';
}