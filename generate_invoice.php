<?php

/**
 * Plugin Name: WooCommerce Order Email and Invoice Generator
 * Description: Automatically generates a PDF invoice when a new WooCommerce order is placed.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}


add_action('woocommerce_new_order', 'generate_order_confirmation_email_and_invoice_pdf');
function  generate_order_confirmation_email_and_invoice_pdf($order_id)
{

    require plugin_dir_path(__FILE__) . '/vendor/autoload.php';
    $upload_dir = wp_upload_dir();
    $custom_temp_dir = $upload_dir['basedir'] . '/mpdf_temp/';
    if (!file_exists($custom_temp_dir)) {
        mkdir($custom_temp_dir, 0755, true);
    }

    $mpdf = new \Mpdf\Mpdf(['tempDir' => $custom_temp_dir]);
    error_log("mpdf initialized");

    $order = wc_get_order($order_id);
    $logo_url = get_site_icon_url();
    $html = "";
    $html .= "<div style='font-family: Arial, sans-serif; background-color: #f9f9f9; color: #333; padding: 20px; border-radius: 10px; max-width: 800px; margin: 0 auto;'>
        <div style='background-color: #7c42da; padding: 20px; border-radius: 10px; max-width: 800px; margin: 0 auto;'>
            <div style='text-align: center;'>
                <img src='" . $logo_url . "' alt='Logo' style='width: 100px; height: auto; border-radius:25px;'>
            </div>
        </div>
        <h2 style='text-align: center; color: #ff7e5f;'>Hi " . $order->get_shipping_first_name() . "</h2>
        <p style='font-size: 16px; line-height: 1.6;'>We're prepping your bag of joy, containing the products you picked. Once we've shipped your order, you'll get an email with your shipping and tracking information.</p>
        <p style='font-size: 16px; line-height: 1.6;'>We know you can't wait to get your hands on it, so we've begun prepping for it right away.</p>
        <p style='font-size: 16px; line-height: 1.6;'>In the meantime, you can track your order below.</p>
        <h4 style='text-align: center; color: #ff7e5f;'>Stay Stylish!</h4>
        <p style='text-align: center;'>Team WordPress</p>";


    $html .= '<hr style="height: 10px; background: linear-gradient(45deg, rgba(255, 126, 95, 0.9), rgba(254, 180, 123, 0.9), rgba(255, 219, 120, 0.8));
margin: 20px 0;">';

    $html .= '<h1 style="font-size: 24px; text-align: center;">Order No: #' . $order_id . '-' . time() . '</h1>';
    $html .= '<p><strong>Date:</strong> ' . $order->get_date_created()->format('F j, Y') . '</p>';

    $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 16px;">';
    $html .= '<thead><tr style="background-color: #f3f3f3; font-weight: bold;">';
    $html .= '<th style="padding: 10px; text-align: left;">Billing Address</th>';
    $html .= '<th style="padding: 10px; text-align: left;">Shipping Address</th><tr>';
    $html .= '</tr></thead><tbody>';
    $html .= '<tr><td style="padding: 10px;">' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ', ' . $order->get_billing_address_1() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_postcode() . '</td>';
    $html .= '<td style="padding: 10px;">' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . ', ' . $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . '</td></tr>';
    $html .= '</tbody></table>';

    $html .= '<hr style="height: 10px; background: linear-gradient(45deg, rgba(255, 126, 95, 0.9), rgba(254, 180, 123, 0.9), rgba(255, 219, 120, 0.8));
margin: 20px 0;">';

    $html .= '<h3 style="text-align: center; color: #7c42da;">Your Order Details</h3>';
    $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 16px;">';
    $html .= '<thead><tr style="background-color: #f3f3f3; font-weight: bold;">';
    $html .= '<th style="padding: 10px; text-align: left;">Product</th>';
    $html .= '<th style="padding: 10px; text-align: left;">Price</th>';
    $html .= '<th style="padding: 10px; text-align: left;">Quantity</th>';
    $html .= '<th style="padding: 10px; text-align: left;">Image</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($order->get_items() as $item_id => $item) {
        if (!$item instanceof WC_Order_Item_Product) {
            continue;
        }
        $product = $item->get_product();
        $product_name = $item->get_name();
        $product_quantity = $item->get_quantity();
        $product_price = $order->get_formatted_line_subtotal($item);
        $product_image = wp_get_attachment_url($product->get_image_id());

        $html .= '<tr>';
        $html .= '<td style="padding: 10px;">' . $product_name . '</td>';
        $html .= '<td style="padding: 10px;">' . $product_price . '</td>';
        $html .= '<td style="padding: 10px;">' . $product_quantity . '</td>';
        if ($product_image) {
            $html .= '<td style="padding: 10px;"><img src="' . $product_image . '" alt="' . $product_name . '" style="width: 80px; height: 80px; object-fit: cover;"></td>';
        } else {
            $html .= '<td style="padding: 10px;">No image available</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    $html .= '<hr style="height: 10px; background: linear-gradient(45deg, rgba(255, 126, 95, 0.9), rgba(254, 180, 123, 0.9), rgba(255, 219, 120, 0.8));
margin: 20px 0;">';

    $html .= '<h3 style="text-align: center; color: #7c42da;">Billing Details</h3>';
    $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 16px; margin-top: 20px;">';
    $html .= '<tr><th style="padding: 10px; background-color: #f3f3f3; text-align: left;">Package Value</th><td style="padding: 10px;">₹ ' . $order->get_total() . '</td></tr>';
    $html .= '<tr><th style="padding: 10px; background-color: #f3f3f3; text-align: left;">Tax</th><td style="padding: 10px;">₹ ' . $order->get_total_tax() . '</td></tr>';
    $html .= '<tr><th style="padding: 10px; background-color: #f3f3f3; text-align: left;">Shipping Charge</th><td style="padding: 10px;">₹ ' . $order->get_shipping_total() . '</td></tr>';
    $html .= '<tr><th style="padding: 10px; background-color: #f3f3f3; text-align: left;">Coupon Discount</th><td style="padding: 10px;">- ₹ ' . $order->get_discount_total() . '</td></tr>';
    $html .= '<tr><th style="padding: 10px; background-color: #f3f3f3; text-align: left;">Total</th><td style="padding: 10px;">₹ ' . $order->get_total() . '</td></tr>';
    $html .= '<tr><th style="padding: 10px; background-color: #f3f3f3; text-align: left;">Mode of Payment</th><td style="padding: 10px;">' . $order->get_payment_method_title() . '</td></tr>';
    $html .= '</table>';

    $tracking_url = 'https://www.example.com/track-order?order_id=' . $order_id;
    $html .= '<p style="text-align: center; margin-top: 20px;"><a href="' . $tracking_url . '" style="background-color: #ff7043; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;">Track Order</a></p>';

    $html .= '<p style="text-align: center; margin-top: 20px;">Stay Stylish!</p>';
    $html .= '<p style="text-align: center;">Team WordPress</p>';
    $html .= '<p style="text-align: center;">If you want to reach us, please <a href="https://www.woocommerce.com/contact-us" target="_blank" style="color: #ff7043; text-decoration: none;">Contact Us</a>.</p>';
    $html .= "</div>";
    $mpdf->WriteHTML($html);

    $file_path = $upload_dir['path'] . '/' . $order_id . '-' . time() . '_invoice.pdf';
    $mpdf->Output($file_path, 'F');

    $file_url = $upload_dir['url'] . '/' . $order_id . '-' . time() .  '_invoice.pdf';


    wp_mail($order->get_billing_email(), 'Your Order Confirmation Mail', $html, array('Content-Type: text/html; charset=UTF-8'),  $file_path);
}
