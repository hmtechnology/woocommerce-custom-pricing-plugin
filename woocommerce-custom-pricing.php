<?php 
/*
Plugin Name: WooCommerce Custom Pricing and Purchasability Manager
Description: Empowers users to set custom prices for products and specify whether a product is purchasable directly.
Version: 1.0
Author: hmtechnology
Author URI: https://github.com/hmtechnology
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
Plugin URI: https://github.com/hmtechnology/woocommerce-custom-pricing-plugin
*/

// Add not purchasable checkbox to the product general data options.
add_action('woocommerce_product_options_general_product_data', 'add_not_purchasable_checkbox');

function add_not_purchasable_checkbox() {
    global $post;

    echo '<div class="options_group">';

    woocommerce_wp_checkbox(array(
        'id'            => '_not_purchasable',
        'label'         => __('Not purchasable directly', 'woocommerce'),
        'description'   => __('Check this option if the product is not purchasable directly.', 'woocommerce'),
    ));

    echo '</div>';
}


// Save not purchasable checkbox value when the product meta is processed.
add_action('woocommerce_process_product_meta', 'save_not_purchasable_checkbox');

function save_not_purchasable_checkbox($post_id) {
    $not_purchasable = isset($_POST['_not_purchasable']) ? 'yes' : 'no';
    update_post_meta($post_id, '_not_purchasable', $not_purchasable);
}

// Add a custom price range field to the product in the backend
add_action('woocommerce_product_options_pricing', 'add_field_product_options_pricing');
function add_field_product_options_pricing() {
    global $post;

    echo '<div class="options_group show_if_simple">';

    woocommerce_wp_text_input(array(
        'id'            => '_max_price_for_range',
        'label'         => __('Maximum price for range', 'woocommerce') . ' (' . get_woocommerce_currency_symbol() . ')',
        'placeholder'   => __('Set the maximum price for the range', 'woocommerce'),
        'description'   => __('Set the maximum price for the range to activate it...', 'woocommerce'),
        'desc_tip'      => 'true',
        'type'          => 'number',
        'custom_attributes' => array(
            'step' => '0.01',
        ),
    ));

    echo '</div>';
}

// Save the product custom field to the database when pushed to the backend
add_action('woocommerce_process_product_meta', 'save_product_options_custom_fields', 30, 1);
function save_product_options_custom_fields($post_id) {
    if (isset($_POST['_max_price_for_range']) && $_POST['_max_price_for_range'] === '') {
        delete_post_meta($post_id, '_max_price_for_range');
    } else {
        if (isset($_POST['_max_price_for_range']) && is_numeric($_POST['_max_price_for_range'])) {
            update_post_meta($post_id, '_max_price_for_range', sanitize_text_field($_POST['_max_price_for_range']));
        }
    }
}

// Frontend: display a price range when the maximum price is set for the product
add_filter('woocommerce_get_price_html', 'custom_range_price_format', 10, 2);
function custom_range_price_format($price, $product) {
    if ($product->is_type('simple')) {
        $max_price = get_post_meta($product->get_id(), '_max_price_for_range', true);

        if (!empty($max_price)) {
            $active_price = wc_get_price_to_display($product, array('price' => $product->get_price()));
            $price = sprintf('%s - %s', wc_price($active_price), wc_price($max_price));
        }
    }

    return $price;
}


// Add a custom field to the user profile for the product ID and custom price
add_action('show_user_profile', 'add_custom_price_fields');
add_action('edit_user_profile', 'add_custom_price_fields');
add_action('personal_options_update', 'save_custom_price_fields');
add_action('edit_user_profile_update', 'save_custom_price_fields');

function add_custom_price_fields($user) {
    ?>
    <h3><?php _e('Custom Prices', 'your_textdomain'); ?></h3>
    <table class="form-table" id="custom_price_fields">
        <?php
        $custom_prices = get_user_meta($user->ID, 'custom_prices', true);
        if ($custom_prices && is_array($custom_prices)) {
            foreach ($custom_prices as $index => $custom_price) {
                $product_id = isset($custom_price['product_id']) ? esc_attr($custom_price['product_id']) : '';
                $price = isset($custom_price['price']) ? esc_attr($custom_price['price']) : '';
                $note = isset($custom_price['note']) ? esc_attr($custom_price['note']) : ''; // Aggiunto il campo note
                ?>
                <tr>
                    <th><label for="custom_price_product_id_<?php echo $index; ?>"><?php _e('Product ID', 'your_textdomain'); ?></label></th>
                    <td>
                        <input type="text" name="custom_price_product_id[]" id="custom_price_product_id_<?php echo $index; ?>" value="<?php echo $product_id; ?>" class="regular-text" /><br />
                    </td>
                </tr>
                <tr>
                    <th><label for="custom_price_product_<?php echo $index; ?>"><?php _e('Custom Price', 'your_textdomain'); ?></label></th>
                    <td>
                        <input type="number" name="custom_price_product[]" id="custom_price_product_<?php echo $index; ?>" value="<?php echo $price; ?>" class="regular-text" /><br />
                    </td>
                </tr>
                <tr>
                    <th><label for="custom_price_note_<?php echo $index; ?>"><?php _e('Note', 'your_textdomain'); ?></label></th>
                    <td>
                        <textarea name="custom_price_note[]" id="custom_price_note_<?php echo $index; ?>" class="regular-text"><?php echo $note; ?></textarea><br />
                    </td>
                </tr>
                <?php
            }
        }
        ?>
    </table>
    <button type="button" class="button" id="add_custom_price_field"><?php _e('Add Custom Price Field', 'your_textdomain'); ?></button>
    <script>
        jQuery(document).ready(function ($) {
            $('#add_custom_price_field').on('click', function () {
                var index = $('#custom_price_fields tr').length / 3; // Calcola l'indice del nuovo campo (3 righe per campo)
                $('#custom_price_fields').append(`
                    <tr>
                        <th><label for="custom_price_product_id_${index}"><?php _e('Product ID', 'your_textdomain'); ?></label></th>
                        <td>
                            <input type="text" name="custom_price_product_id[]" id="custom_price_product_id_${index}" class="regular-text" /><br />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="custom_price_product_${index}"><?php _e('Custom Price', 'your_textdomain'); ?></label></th>
                        <td>
                            <input type="number" name="custom_price_product[]" id="custom_price_product_${index}" class="regular-text" /><br />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="custom_price_note_${index}"><?php _e('Note', 'your_textdomain'); ?></label></th>
                        <td>
                            <textarea name="custom_price_note[]" id="custom_price_note_${index}" class="regular-text"></textarea><br />
                        </td>
                    </tr>
                `);
            });
        });
    </script>
    <?php
}

// Save custom price fields for the user
function save_custom_price_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

	if (isset($_POST['custom_price_product_id']) && isset($_POST['custom_price_product'])) {
        $custom_prices = array();
        $product_ids = $_POST['custom_price_product_id'];
        $prices = $_POST['custom_price_product'];
        $notes = isset($_POST['custom_price_note']) ? $_POST['custom_price_note'] : array();
        foreach ($product_ids as $index => $product_id) {
            $product_id = sanitize_text_field($product_id);
            $price = isset($prices[$index]) ? sanitize_text_field($prices[$index]) : '';
            $note = isset($notes[$index]) ? sanitize_text_field($notes[$index]) : '';
            if (!empty($product_id)) {
                $custom_prices[] = array(
                    'product_id' => $product_id,
                    'price' => $price,
                    'note' => $note,
                );
            }
        }
        update_user_meta($user_id, 'custom_prices', $custom_prices);
    }
}

// Update the product price based on the customer's custom price
add_filter('woocommerce_get_price_html', 'custom_price_based_on_customer_and_product', 10, 2);

function custom_price_based_on_customer_and_product($price_html, $product) {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);
        if (!empty($custom_prices)) {
            foreach ($custom_prices as $custom_price) {
                if ($custom_price['product_id'] == $product->get_id() && !empty($custom_price['price'])) {
                    $price_html = wc_price($custom_price['price']);
                    if (!empty($custom_price['note'])) {
                        $price_html .= '<br/><small><span class="custom-price-note">' . esc_html($custom_price['note']) . '</span></small>';
                    }
                    return $price_html;
                }
            }
        }
    }
    return $price_html;
}

// Update cart item price based on customer's custom price
add_action('woocommerce_before_calculate_totals', 'update_cart_item_custom_price', 10, 1);

function update_cart_item_custom_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product($product_id);

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);

            if (!empty($custom_prices)) {
                foreach ($custom_prices as $custom_price) {
                    if ($custom_price['product_id'] == $product_id && !empty($custom_price['price'])) {
                        $cart_item['data']->set_price($custom_price['price']);
                        break;
                    }
                }
            }
        }
    }
}

// Update cart item price based on customer's custom price during checkout
add_action('woocommerce_before_calculate_totals', 'update_checkout_item_custom_price', 10, 1);

function update_checkout_item_custom_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product($product_id);

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);

            if (!empty($custom_prices)) {
                foreach ($custom_prices as $custom_price) {
                    if ($custom_price['product_id'] == $product_id && !empty($custom_price['price'])) {
                        $cart_item['data']->set_price($custom_price['price']);
                        break;
                    }
                }
            }
        }
    }
}

// Save custom price notes as order item meta
add_action('woocommerce_checkout_create_order_line_item', 'save_custom_price_notes_in_order', 10, 4);

function save_custom_price_notes_in_order($item, $cart_item_key, $values, $order) {
    $product_id = $item->get_product_id();

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);

        if (!empty($custom_prices)) {
            foreach ($custom_prices as $custom_price) {
                if ($custom_price['product_id'] == $product_id && !empty($custom_price['note'])) {
                    $item->add_meta_data(__('Prezzo Personalizzato', 'woocommerce'), $custom_price['note']);
                    break;
                }
            }
        }
    }
}

// Display custom price notes in cart and checkout
add_filter('woocommerce_get_item_data', 'display_custom_price_notes_in_cart', 10, 2);
add_filter('woocommerce_order_item_product', 'display_custom_price_notes_in_order', 10, 2);

function display_custom_price_notes_in_cart($cart_data, $cart_item) {
    if (is_array($cart_data)) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product($product_id);

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);

            if (!empty($custom_prices)) {
                foreach ($custom_prices as $custom_price) {
                    if ($custom_price['product_id'] == $product_id && !empty($custom_price['note'])) {
                        $cart_data[] = array(
                            'name' => __('Nota', 'woocommerce'),
                            'value' => $custom_price['note']
                        );
                        break;
                    }
                }
            }
        }
    }

    return $cart_data;
}

function display_custom_price_notes_in_order($product, $order_item) {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);

        if (!empty($custom_prices)) {
            foreach ($custom_prices as $custom_price) {
                if ($custom_price['product_id'] == $product->get_id() && !empty($custom_price['note'])) {
                    echo '<br/><small><span class="custom-price-note">' . esc_html($custom_price['note']) . '</span></small>';
                    break;
                }
            }
        }
    }
}

// Remove button "Add to Cart" if the product is not purchasable or if a custom price is not set
add_filter('woocommerce_is_purchasable', 'check_product_purchasability', 10, 2);

function check_product_purchasability($purchasable, $product) {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $custom_prices = get_user_meta($current_user->ID, 'custom_prices', true);

        if (!empty($custom_prices)) {
            foreach ($custom_prices as $custom_price) {
                if ($custom_price['product_id'] == $product->get_id() && !empty($custom_price['price'])) {
                    return true;
                }
            }
        }
    }

    $not_purchasable = get_post_meta($product->get_id(), '_not_purchasable', true);

    if ($not_purchasable === 'yes') {
        return false;
    }

    return $purchasable;
}

// Add button "Request a Quote" for products not purchasable
add_action('woocommerce_single_product_summary', 'add_request_quote_button', 25);

function add_request_quote_button() {
    global $product;

    if (!is_product_purchasable($product->get_id())) {
        echo '<a href="/preventivo" target="_blank" class="request-quote-button button" style="display: inline-block; padding: 10px 20px; background-color: #a82a6f;color: #fff; text-decoration: none; border: none; ">Richiedi un Preventivo</a>';
    }
}

// Custom function to check if a product is purchasable
function is_product_purchasable($product_id) {
    $product = wc_get_product($product_id);
    return $product && $product->is_purchasable();
}
