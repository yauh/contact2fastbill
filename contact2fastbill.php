<?php
/*
Plugin Name: Contact2FastBill
Plugin URI: http://yauh.de/
Description: Create new customer in FastBill based on Contact 7 contact form,
             tested and compatible with Contact Form 7 v 4.0
Author: Stephan Hochhaus
Version: 0.2 - 2014-12-02
Author URI: http://yauh.de/
*/

// before sending the e-mail make sure to add the customer to FastBill
add_action( "wpcf7_before_send_mail", "wpcf7_contact2fastbill" );

function wpcf7_contact2fastbill( $cf7 ) {
	// Check whether this form should be processed at all

	$submittedData = WPCF7_Submission::get_instance();

	if ( $submittedData ) {
		$formData = $submittedData->get_posted_data();
		if ( $formData['_wpcf7'] !== get_option( 'contact2fastbill_formId' ) ) {
			return;
		}
		/* In the future we may support other services, only Fastbill for now */
		require( 'fastbill.1.2.php' );


		/* configuration settings */
		$apiUrl  = get_option( 'contact2fastbill_apiUrl' );
		$apiUser = get_option( 'contact2fastbill_apiUser' );
		$apiKey  = get_option( 'contact2fastbill_apiKey' );

		// getting customer data from form
		$customerEmail = $formData[ get_option( 'contact2fastbill_customerEmail' ) ];
		$salutation    = $formData[ get_option( 'contact2fastbill_salutation' ) ];
		if ( $salutation == 'Herr' ) {
			$salutation = 'mr';
		} else if ( $salutation == 'Frau' ) {
			$salutation = 'mrs';
		}
		$firstName    = $formData[ get_option( 'contact2fastbill_firstName' ) ];
		$lastName     = $formData[ get_option( 'contact2fastbill_lastName' ) ];
		$phone        = $formData[ get_option( 'contact2fastbill_phone' ) ];
		$customerType = get_option( 'contact2fastbill_customerType' );
		$tags         = get_option( 'contact2fastbill_tags' );
		$countryCode  = get_option( 'contact2fastbill_countryCode' );
		$paymentType  = get_option( 'contact2fastbill_paymentType' ); // 5 - vorkasse, 3 - bar
		$address      = $formData[ get_option( 'contact2fastbill_address' ) ];
		$zipCode      = $formData[ get_option( 'contact2fastbill_zipCode' ) ];
		$city         = $formData[ get_option( 'contact2fastbill_city' ) ];

		/* here's where the magic happens */
		// create new connection to fastbill API
		$fastbill = new fastbill( $apiUser, $apiKey, $apiUrl );

		// test whether customer with this email already exists
		$result = $fastbill->request( array(
			"SERVICE" => "customer.get",
			"FILTER"  => array( "TERM" => $customerEmail )
		) );

		if ( count( $result['RESPONSE']['CUSTOMERS'] ) == 0 ) { // if no results found create a new customer
			$create     = $fastbill->request( array(
				"SERVICE" => "customer.create",
				"DATA"    => array(
					"CUSTOMER_TYPE" => $customerType,
					"SALUTATION"    => $salutation,
					"FIRST_NAME"    => $firstName,
					"LAST_NAME"     => $lastName,
					"EMAIL"         => $customerEmail,
					"PHONE"         => $phone,
					"ADDRESS"       => $address,
					"ZIPCODE"       => $zipCode,
					"CITY"          => $city,
					"COUNTRY_CODE"  => $countryCode,
					"PAYMENT_TYPE"  => $paymentType,
					"TAGS"          => $tags
				)
			) );
			$customerId = $create['RESPONSE']['CUSTOMER_ID'];

// add Fastbill info to mail body
			$mail = $cf7->prop( 'mail' );
			$mail['body'] .= "\nFastbill ID: $customerId";
			$cf7->set_properties( array( 'mail' => $mail ) );

		} else { // skip customer creation if contact already has an ID
			$customerId     = $result['RESPONSE']['CUSTOMERS'][0]['CUSTOMER_ID'];
			$customerNumber = $result['RESPONSE']['CUSTOMERS'][0]['CUSTOMER_NUMBER'];

			// add Fastbill info to mail body
			$mail = $cf7->prop( 'mail' );
			$mail['body'] .= "\nFastbill ID: $customerId, Kdnr.: $customerNumber";
			$cf7->set_properties( array( 'mail' => $mail ) );
		}
	}

	return false;
}

/* Register settings for admin panel */
function contact2fastbill_register_settings() {
	add_option( 'contact2fastbill_apiUrl', 'https://my.fastbill.com/api/1.0/api.php' );
	add_option( 'contact2fastbill_apiUser', 'EnterUserEmail' );
	add_option( 'contact2fastbill_apiKey', 'EnterApiKey' );
	add_option( 'contact2fastbill_address', 'loc_street' );
	add_option( 'contact2fastbill_city', 'loc_city' );
	add_option( 'contact2fastbill_countryCode', 'DE' );
	add_option( 'contact2fastbill_customerEmail', 'Email' );
	add_option( 'contact2fastbill_customerType', 'consumer' );
	add_option( 'contact2fastbill_firstName', 'FirstName' );
	add_option( 'contact2fastbill_formId', '390' );
	add_option( 'contact2fastbill_lastName', 'LastName' );
	add_option( 'contact2fastbill_paymentType', '5' );
	add_option( 'contact2fastbill_phone', 'HomePhone' );
	add_option( 'contact2fastbill_salutation', 'salut' );
	add_option( 'contact2fastbill_tags', 'kontaktformular' );
	add_option( 'contact2fastbill_zipcode', 'loc_zip' );
	add_option( 'contact2fastbill_add_id', true );
	// API settings
	register_setting( 'default', 'contact2fastbill_apiUrl' );
	register_setting( 'default', 'contact2fastbill_apiUser' );
	register_setting( 'default', 'contact2fastbill_apiKey' );
	// Field Matching
	register_setting( 'default', 'contact2fastbill_address' );
	register_setting( 'default', 'contact2fastbill_city' );
	register_setting( 'default', 'contact2fastbill_countryCode' );
	register_setting( 'default', 'contact2fastbill_customerEmail' );
	register_setting( 'default', 'contact2fastbill_customerType' );
	register_setting( 'default', 'contact2fastbill_firstName' );
	register_setting( 'default', 'contact2fastbill_formId' );
	register_setting( 'default', 'contact2fastbill_lastName' );
	register_setting( 'default', 'contact2fastbill_paymentType' );
	register_setting( 'default', 'contact2fastbill_phone' );
	register_setting( 'default', 'contact2fastbill_salutation' );
	register_setting( 'default', 'contact2fastbill_tags' );
	register_setting( 'default', 'contact2fastbill_zipcode' );
	register_setting( 'default', 'contact2fastbill_add_id' );
}

add_action( 'admin_init', 'contact2fastbill_register_settings' );

/* Register options page for admin panel */

function contact2fastbill_register_options_page() {
	add_options_page( 'Page title', 'Contact2FastBill', 'manage_options', 'contact2fastbill-options', 'contact2fastbill_options_page' );
}

add_action( 'admin_menu', 'contact2fastbill_register_options_page' );

function contact2fastbill_options_page() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2>Contact2FastBill Settings</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'default' ); ?>
			<h3>API Settings</h3>

			<p>These are the credentials that you will use to connect to FastBill. You can find the API Key on your
				settings page inside FastBill.</p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_apiUrl">FastBill API URL*</label></th>
					<td><input type="text" id="contact2fastbill_apiUrl" name="contact2fastbill_apiUrl"
					           value="<?php echo get_option( 'contact2fastbill_apiUrl' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_apiUser">API User (E-Mail)*</label></th>
					<td><input type="text" id="contact2fastbill_apiUser" name="contact2fastbill_apiUser"
					           value="<?php echo get_option( 'contact2fastbill_apiUser' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_apiKey">API Key*</label></th>
					<td><input type="text" id="contact2fastbill_apiKey" name="contact2fastbill_apiKey"
					           value="<?php echo get_option( 'contact2fastbill_apiKey' ); ?>"/></td>
				</tr>
			</table>
			<h3>Field Mapping</h3>

			<p>Here you need to define the name of each Contact7 field that will be associated with the fields in
				FastBill.</p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_formId">Form ID</label></th>
					<td><input type="text" id="contact2fastbill_formId" name="contact2fastbill_formId"
					           value="<?php echo get_option( 'contact2fastbill_formId' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_salutation">Salutation</label></th>
					<td><input type="text" id="contact2fastbill_salutation" name="contact2fastbill_salutation"
					           value="<?php echo get_option( 'contact2fastbill_salutation' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_customerEmail">Customer E-Mail</label></th>
					<td><input type="text" id="contact2fastbill_customerEmail" name="contact2fastbill_customerEmail"
					           value="<?php echo get_option( 'contact2fastbill_customerEmail' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_firstName">Customer First Name</label></th>
					<td><input type="text" id="contact2fastbill_firstName" name="contact2fastbill_firstName"
					           value="<?php echo get_option( 'contact2fastbill_firstName' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_lastName">Customer Last Name*</label></th>
					<td><input type="text" id="contact2fastbill_lastName" name="contact2fastbill_lastName"
					           value="<?php echo get_option( 'contact2fastbill_lastName' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_phone">Customer Phone</label></th>
					<td><input type="text" id="contact2fastbill_phone" name="contact2fastbill_phone"
					           value="<?php echo get_option( 'contact2fastbill_phone' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_address">Customer Address</label></th>
					<td><input type="text" id="contact2fastbill_address" name="contact2fastbill_address"
					           value="<?php echo get_option( 'contact2fastbill_address' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_city">Customer City</label></th>
					<td><input type="text" id="contact2fastbill_city" name="contact2fastbill_city"
					           value="<?php echo get_option( 'contact2fastbill_city' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_zipcode">Customer ZIP code</label></th>
					<td><input type="text" id="contact2fastbill_zipcode" name="contact2fastbill_zipcode"
					           value="<?php echo get_option( 'contact2fastbill_zipcode' ); ?>"/></td>
				</tr>

			</table>
			<h3>Others</h3>

			<p>These are various settings. See also the <a href="http://www.fastbill.com/api/kunden.html">API
					documentation</a> for further reference.</p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_customerType">Default Customer Type*</label></th>
					<td><input type="text" id="contact2fastbill_customerType" name="contact2fastbill_customerType"
					           value="<?php echo get_option( 'contact2fastbill_customerType' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_countryCode">Default Country Code*</label></th>
					<td><input type="text" id="contact2fastbill_countryCode" name="contact2fastbill_countryCode"
					           value="<?php echo get_option( 'contact2fastbill_countryCode' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_paymentType">Payment Type*</label></th>
					<td><input type="text" id="contact2fastbill_paymentType" name="contact2fastbill_paymentType"
					           value="<?php echo get_option( 'contact2fastbill_paymentType' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_tags">Tags for new contacts</label></th>
					<td><input type="text" id="contact2fastbill_tags" name="contact2fastbill_tags"
					           value="<?php echo get_option( 'contact2fastbill_tags' ); ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="contact2fastbill_add_id">Add ID to form e-mail</label></th>
					<td><input type="text" id="contact2fastbill_add_id" name="contact2fastbill_add_id"
					           value="<?php echo get_option( 'contact2fastbill_add_id' ); ?>"/></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
<?php

}

?>
