<?php

use Netlipress\Commerce;

function add_to_cart_button($buttonText = 'Add to cart', $addedText = 'Added to cart', $cartLinkText = 'View cart')
{
    echo '<div class="add-to-cart">';
    echo '<input type="number" name="quantity" value="1"/>';
    echo '<button class="button" data-action="add_product_to_cart" data-product="' . get_the_permalink() . '" data-added-text="'.$addedText.'" data-link-text="'.$cartLinkText.'">';
    echo $buttonText;
    echo '</button>';
    echo '<div class="added-to-cart" style="display:none">' . $addedText . '<a class="button small" href="/checkout">' . $cartLinkText . '</a></div>';
    echo '</div>';
}

function cart_button($text = 'View cart')
{
    echo '<a href="/checkout" class="cart-button">';
    echo $text;
    echo ' <span class="count">' . Commerce::getCartItemCount() . '</span>';
    echo '</a>';
}

function format_currency($value)
{
    $fmt = numfmt_create('nl_NL', NumberFormatter::CURRENCY);
    echo numfmt_format_currency($fmt, $value, "EUR");
}

function commerce_cart_contents()
{
    $products = [];
    foreach ($_SESSION['cart'] as $key => $details) {
        $productFile = APP_ROOT . CONTENT_DIR . $key . '.json';
        $product = get_post($productFile);
        $product->quantity = $details['quantity'];
        $product->slug = $key;
        $products[] = $product;
    }
    return $products;
}

function get_cart_subtotal() {
    $products = commerce_cart_contents();
    $money = 0;
    foreach(commerce_cart_contents() as $product) {
        $money += $product->price * $product->quantity;
    }
    return $money;
}

function the_cart_subtotal() {
    echo format_currency(get_cart_subtotal());
}

function get_cart_shipping() {
    //TODO: Make this a setting somewhere.
    //TODO: Free shipping after x amount
    return 6.95;
}

function the_cart_shipping() {
    echo format_currency(get_cart_shipping());
}

function get_cart_total() {
    return get_cart_subtotal() + get_cart_shipping();
}

function the_cart_total() {
    echo format_currency(get_cart_total());
}
