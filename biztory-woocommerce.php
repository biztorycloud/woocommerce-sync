<?php

/**
 * Plugin Name: Biztory WooCommerce Plugin
 * Description: Connect your Biztory account with Woocommerce.
 * Version: 1.0.2
 * Author: Oscar
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add a new settings tab to the WooCommerce settings page.
add_filter( 'woocommerce_settings_tabs_array', 'biztory_add_settings_tab', 50 );

function biztory_add_settings_tab( $tabs ) {
    $tabs['biztory'] = __( 'Biztory Settings', 'woocommerce' );
    return $tabs;
}

// Add the new settings fields to the My Plugin tab.
add_action( 'woocommerce_settings_tabs_biztory', 'biztory_settings_tab' );

function biztory_settings_tab() {
    woocommerce_admin_fields( biztory_settings_fields() );
}

// Define the new settings fields.
function biztory_settings_fields() {
    $logger = wc_get_logger();
    $token = get_option( 'biztory_token' );
    $subdomain = get_option( 'biztory_subdomain' );
    $fields = array(
        array(
            'name' => __( 'Biztory Subdomain', 'biztory' ),
            'type' => 'text',
            'id'   => 'biztory_subdomain',
            'desc' => __( 'Enter your Biztory Subdomain.', 'biztory' ),
            'default' => '',
            'suffix' => __( '.biztory.com.my', 'biztory' ),
        ),
        array(
            'name' => __( 'Biztory API Key', 'biztory' ),
            'type' => 'text',
            'id'   => 'biztory_token',
            'desc' => sprintf( __( 'Enter your Biztory API Key. <a href="%s" target="_blank">Learn more</a>', 'biztory' ), 'https://biztory.freshdesk.com/a/solutions/articles/16000110183' ),
            'default' => '',
            'suffix' => __( '', 'biztory' ),
        ),
    );

    if($token && $subdomain) {
        $resp = api_call('account','GET');
        $accounts = json_decode($resp, 1);
        $accounts = array_column($accounts, 'name', 'id');

        $resp = api_call('payment_method','GET', ['type' => 'receivable']);
        $payment_methods = json_decode($resp, 1);
		
        $direct_bank_transfer = array(
            'name' => __( 'Direct bank transfer', 'biztory' ),
			'name2' => __( 'Biztory Payment Method', 'biztory' ),
            'type' => 'select',
            'id'   => 'biztory_payment_method_id_direct_bank_transfer',
            'default' => '',
            'suffix' => __( '', 'biztory' ),
            'options' => []
        );

        $check_payment = array(
            'name' => __( 'Check payments', 'biztory' ),
            'type' => 'select',
            'id'   => 'biztory_payment_method_id_check_payments',
            'default' => '',
            'suffix' => __( '', 'biztory' ),
            'options' => []
        );
		
		$cash_on_delivery = array(
            'name' => __( 'Cash on delivery', 'biztory' ),
            'type' => 'select',
            'id'   => 'biztory_payment_method_id_cash_on_delivery',
			'desc' => 'When synced order, Biztory will create transaction with this payment method',
            'default' => '',
            'suffix' => __( '', 'biztory' ),
            'options' => []
        );

        foreach ($payment_methods as $d) {
			$direct_bank_transfer['options'][$d['id']] = '['.$accounts[$d['account_id']].'] '.$d['name'];
            $check_payment['options'][$d['id']] = '['.$accounts[$d['account_id']].'] '.$d['name'];
			$cash_on_delivery['options'][$d['id']] = '['.$accounts[$d['account_id']].'] '.$d['name'];
		}

        $biztory_payment_methods[] = $direct_bank_transfer;
        $biztory_payment_methods[] = $check_payment;
        $biztory_payment_methods[] = $cash_on_delivery;

        // $logger->debug('Check api call:' . print_r($resp, true));
    }

    // $logger->debug('Check field:' . print_r($fields, true));

    echo '<table class="form-table">';
    foreach ( $fields as $field ) {
        echo '<tr>';
        echo '<th scope="row">' . esc_html( $field['name'] ) . '</th>';
        echo '<td style="width: 100%"><div style="display:inline-block; line-height: ' . esc_attr( intval( get_option('biztory-field-height', '25') ) ) . 'px; height: ' . esc_attr( intval( get_option('biztory-field-height', '25') ) ) . 'px;">';
		
        if($field['type'] == 'select') {
            $account_id = get_option($field['id']);
            echo '<select name="' . esc_attr( $field['id'] ) . '">';
            foreach ($field['options'] as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($account_id, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }
        else {
            echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( get_option( $field['id'], $field['default'] ) ) . '" class="regular-text" pattern="[A-Za-z0-9]+" required/> ';
        }

        echo  esc_html( $field['suffix'] ) . '</div></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="padding-top:0;"></td>';
        echo '<td style="padding-top:0;" colspan="2"><span class="description">' .  $field['desc']  . '</span></td>';
        echo '</tr>';
    }
    echo '</table>';
	
	echo '<table class="form-table">';
    foreach ( $biztory_payment_methods as $biztory_payment_method ) {
        echo '<tr>';
		echo '<th scope="row">' . esc_html( $biztory_payment_method['name2'] ) . '</th>';
        echo '<th scope="row">' . esc_html( $biztory_payment_method['name'] ) . '</th>';
        echo '<td style="width: 100%"><div style="display:inline-block; line-height: ' . esc_attr( intval( get_option('biztory-field-height', '25') ) ) . 'px; height: ' . esc_attr( intval( get_option('biztory-field-height', '25') ) ) . 'px;">';
		
        if($biztory_payment_method['type'] == 'select') {
            $account_id = get_option($biztory_payment_method['id']);
            echo '<select name="' . esc_attr( $biztory_payment_method['id'] ) . '">';
            foreach ($biztory_payment_method['options'] as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($account_id, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }
        else {
            echo '<input type="' . esc_attr( $biztory_payment_method['type'] ) . '" name="' . esc_attr( $biztory_payment_method['id'] ) . '" id="' . esc_attr( $biztory_payment_method['id'] ) . '" value="' . esc_attr( get_option( $biztory_payment_method['id'], $biztory_payment_method['default'] ) ) . '" class="regular-text" pattern="[A-Za-z0-9]+" required/> ';
        }

        echo  esc_html( $biztory_payment_method['suffix'] ) . '</div></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="padding-top:0;"></td>';
		echo '<td style="padding-top:0;" colspan="2"><span class="description">' .  $biztory_payment_method['desc']  . '</span></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Save the token value when the settings are updated.
add_action( 'woocommerce_update_options_biztory', 'biztory_update_settings' );

function biztory_update_settings() {
    update_option( 'biztory_token', sanitize_text_field( $_POST['biztory_token'] ) );
    update_option( 'biztory_subdomain', sanitize_text_field( $_POST['biztory_subdomain'] ) );

    if( isset( $_POST['biztory_payment_method_id_direct_bank_transfer'] ) ) {
        update_option( 'biztory_payment_method_id_direct_bank_transfer', sanitize_text_field( $_POST['biztory_payment_method_id_direct_bank_transfer'] ) );
    }

    if( isset( $_POST['biztory_payment_method_id_check_payments'] ) ) {
        update_option( 'biztory_payment_method_id_check_payments', sanitize_text_field( $_POST['biztory_payment_method_id_check_payments'] ) );
    }
	
	if( isset( $_POST['biztory_payment_method_id_cash_on_delivery'] ) ) {
        update_option( 'biztory_payment_method_id_cash_on_delivery', sanitize_text_field( $_POST['biztory_payment_method_id_cash_on_delivery'] ) );
    }
}

add_filter( 'plugin_action_links', 'biztory_settings_link', 10, 2 );

function biztory_settings_link( $links, $file ) {
    if ( plugin_basename( __FILE__ ) === $file ) {
        $settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=biztory' ), __( 'Settings', 'biztory' ) );
        array_unshift( $links, $settings_link );
    }
    return $links;
}

add_action( 'admin_init', 'biztory_settings_redirect' );

function biztory_settings_redirect() {
    if ( isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && 'biztory' === $_GET['tab'] ) {
        if ( ! isset( $_GET['section'] ) || 'biztory_token' !== $_GET['section'] ) {
            wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=biztory&section=biztory_token' ) );
            exit;
        }
    }
}

// Add a callback function to the woocommerce_payment_complete hook
// add_action( 'woocommerce_payment_complete', 'biztory_payment_complete', 10, 1 );
// add_action( 'woocommerce_payment_complete', 'biztory_payment_complete');
add_action( 'woocommerce_order_status_completed', 'biztory_payment_complete');
// add_action( 'woocommerce_payment_complete_order_status_completed', 'biztory_payment_complete', 10, 1 );

function biztory_payment_complete( $order_id ) {
	$logger = wc_get_logger();
	$logger->debug('Biztory hook');
    date_default_timezone_set('Asia/Kuala_Lumpur');
    // Retrieve the order object using the $order_id parameter
    $order = wc_get_order( $order_id );
    $date = $order->get_date_created()->date('Y-m-d');
    // Check if the order is already paid for
    if(!$order->is_paid()) {
        return true;
    }

    $logger->debug('Biztory start');

    $invoice = prepare_payload($order, $date);

	set_shipping_address($invoice, $order);
    set_shipping_fees($invoice, $order);
    set_payment($invoice, $order, $date);
    set_discount($invoice, $order);
	
    $logger->debug('Biztory send' . print_r($invoice, 1));
    api_call('sale','POST',$invoice);
}

function set_shipping_address(&$invoice, $order) {
    if (!$order->has_shipping_address()) {
        return true;
    }

    $invoice['attn'] = $order->get_shipping_first_name() .' '.$order->get_shipping_last_name();
    $invoice['addr'] = $order->get_shipping_address_1() .' '. $order->get_shipping_address_2() ;
    $invoice['city'] = $order->get_shipping_city();
    $invoice['state'] = $order->get_shipping_state();
    $invoice['zipcode'] = $order->get_shipping_postcode();
}

function api_call($url, $method = 'GET', $data = []) {
    $logger = wc_get_logger();
    $token = get_option('biztory_token');
    $subdomain = get_option('biztory_subdomain');

    $headers = array(
        'Api-key'       => $token,
        'Accept'  => 'application/json',
		'Content-type' => 'application/json',
    );

    $url = 'https://'.$subdomain . '.biztory.com.my/api_v1/' . $url;

    $args = array(
        'headers'   => $headers,
        'method'    => $method,
    );

    if($method == 'POST') {
        $args['body'] = json_encode($data);
    }
    else {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_post( $url, $args );

    // Check if the API call was successful
    if ( is_wp_error( $response ) ) {
        // Handle the error
        $error_message = $response->get_error_message();
        $logger->debug('Biztory error:' . $error_message );

    } else {
        // Handle the response
        $response_body = wp_remote_retrieve_body( $response );
        // Do something with the response body
        $logger->debug('Biztory success:' . $response_body);
        return $response_body;
    }
}

function prepare_payload($order, $date) {
	$resp = api_call('account', 'GET');
	$accounts = json_decode($resp, true);
	$accounts = array_column($accounts, 'name', 'id');

	$resp = api_call('payment_method', 'GET', ['type' => 'receivable']);
	$payment_methods = json_decode($resp, true);

	$id = get_payment_term_name($order->get_payment_method());

	foreach ($payment_methods as $d) {
    	if ($d['id'] == $id) {
        	$name = '[' . $accounts[$d['account_id']] . '] ' . $d['name'];
    	}
	}

    $invoice =  array(
        'invoice_date'      => $date,
        'gst_supply_date'   => $date,
        'payee'             => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'grandTotal'        => $order->get_total(),
        'is_gst'            => 0,
        'is_sst'            => 0,
        'is_receipt'        => 0,
        'tax_inclusive'     => 0,
        'not_mixed_supply'  => 1,
        'phone'             => $order->get_billing_phone(),
        'billing_addr'      => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
        'billing_city'      => $order->get_billing_city(),
        'billing_state'     => $order->get_billing_state(),
        'billing_zipcode'   => $order->get_billing_postcode(),
        'items'             => [],
        'payment_term'      => [
            'id' => $id,
            'name' => $name,
        ],
        'terms' => [
            [
                'date'      => $date,
                'amount'    => $order->get_total(),
            ]
        ],
        'customField' => [
            [
                'key'       => 'import_source',
                'value'     => 'woocommerce',
                'printable' => false,
            ],
            [
                'key' => 'woocommerce_order_id',
                'value' => $order->get_id(),
                'printable' => false,
            ],
        ],
    );

    set_items($invoice, $order);
	
	return $invoice;
	
//     $jsonPayload = json_encode($invoice);

//     return $jsonPayload;
}

function get_payment_term_name($woo_payment_method) {
    // Add a switch statement or any logic to map WooCommerce payment methods to Biztory payment term names
	
    switch ($woo_payment_method) {
        case 'bacs':
            return get_option('biztory_payment_method_id_direct_bank_transfer');
        case 'cheque':
            return get_option('biztory_payment_method_id_check_payments');
        case 'cod':
            return get_option('biztory_payment_method_id_cash_on_delivery');
        default:
            return 'Other';
    }
}

function set_shipping_fees(&$invoice, $order) {
    $shipping_fees = floatval($order->get_shipping_total());

    if (!$shipping_fees) {
        return true;
    }

    $invoice['items'][] = [
        'qty'           => 1,
        'price'         => $shipping_fees,
        'total'         => $shipping_fees,
        'code'          => 'shipping',
        'description'   => 'SHIPPING FEE',
    ];
}

function set_payment(&$invoice, $order, $date) {
    $woo_payment_method = $order->get_payment_method();

    if (empty($woo_payment_method)) {
        return true;
    }

    $biztory_payment_method_id = get_payment_term_name($woo_payment_method);

    if ($biztory_payment_method_id) {
        $invoice['transactions'][] = array(
            'amount'            => $order->get_total(),
            'payment_date'      => $date,
            'payment_method_id' => $biztory_payment_method_id,
        );
    }
}

function set_items(&$invoice, $order)
{
    $items = $order->get_items();

    if(empty($items)) {
        return true;
    }

    foreach ($items as $itm) {
        if($itm->get_type() != 'line_item') {
            continue;
        }

        $total = $order->get_line_subtotal($itm, true, false);
        $price =  $order->get_item_subtotal($itm, true, false);
        $product = $itm->get_product();
        $item_data = [
            'qty'           => $itm->get_quantity(),
            'price'         => $price,
            'total'         => $total,
            'total_tax'     => 0.00,
            'code'          => $product->get_sku(),
            'description'   => $product->get_name(),
        ];

        $invoice['items'][] = $item_data;
    }
}

function set_discount(&$invoice, $order) {
    $discount = $order->get_discount_total();

    if (!$discount) {
        return true;
    }

    $invoice['discount'] = [
        'label'	=> 'MYR',
        'shown' => true,
        'total' => $discount,
        'value' => $discount,
    ];
}
