<?php


namespace Netlipress;

use Mollie\Api\MollieApiClient;

class Commerce
{
    private $mollie;

    public function __construct()
    {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey(MOLLIE_API_KEY);
    }

    public static function getCartItemCount()
    {
        $count = 0;
        $cart = $_SESSION['cart'] ?? [];
        foreach ($cart as $product) {
            $count += $product['quantity'];
        }
        return $count;
    }

    public function getCartContents()
    {
        $products = [];
        $cart = $_SESSION['cart'] ?? [];
        foreach ($cart as $key => $details) {
            $productFile = APP_ROOT . CONTENT_DIR . $key . '.json';
            $product = get_post($productFile);
            $product->quantity = $details['quantity'];
            $product->slug = $key;
            $products[] = $product;
        }
        return $products;
    }

    public function handleAjaxActions($action)
    {
        if ($action === 'add_product_to_cart') {
            if (isset($_SESSION['cart'][$_POST['product']])) {
                $_SESSION['cart'][$_POST['product']]['quantity'] += $_POST['quantity'];
            } else {
                $_SESSION['cart'][$_POST['product']] = ['quantity' => $_POST['quantity']];
            }
            echo json_encode(['count' => self::getCartItemCount()]);
            http_response_code(200);
            die();
        }

        if ($action === 'remove_product_from_cart') {
            if (isset($_SESSION['cart'][$_POST['product']])) {
                unset($_SESSION['cart'][$_POST['product']]);
            }
            http_response_code(200);
            die();
        }

        if ($action === 'update_product_quantity') {
            if (isset($_SESSION['cart'][$_POST['product']])) {
                $_SESSION['cart'][$_POST['product']]['quantity'] = $_POST['quantity'];
            }
            http_response_code(200);
            die();
        }

        return false;
    }

    public function createNewOrderFromCart()
    {
        $products = $this->getCartContents();
        $cartValue = 0;
        $vatPercentage = 21;
        $lines = [];
        //Add products
        foreach ($products as $product) {
            $subTotal = $product->price * $product->quantity;
            $cartValue += $subTotal;
            $vatAmount = $subTotal / (100 + $vatPercentage) * $vatPercentage;
            $price = number_format($product->price, 2, '.', '');
            $total = number_format($product->price * $product->quantity, 2, '.', '');
            $vat = number_format($vatAmount, 2, '.', '');
            $vatRate = number_format($vatPercentage, 2, '.', '');

            $lines[] = [
                "name" => $product->title,
                "quantity" => $product->quantity,
                "vatRate" => $vatRate,
                "unitPrice" => [
                    "currency" => "EUR",
                    "value" => $price,
                ],
                "totalAmount" => [
                    "currency" => "EUR",
                    "value" => $total,
                ],
                "vatAmount" => [
                    "currency" => "EUR",
                    "value" => $vat
                ]
            ];
        }
        //Add shipping
        $shipping = number_format(get_cart_shipping(), 2, '.', '');
        $shippingVat = number_format(get_cart_shipping() / (100 + $vatPercentage) * $vatPercentage, 2, '.', '');
        $cartValue += $shipping;
        $lines[] = [
            "name" => "Verzendkosten", //TODO: Translate?
            "quantity" => 1,
            "vatRate" => $vatRate,
            "unitPrice" => [
                "currency" => "EUR",
                "value" => $shipping,
            ],
            "totalAmount" => [
                "currency" => "EUR",
                "value" => $shipping,
            ],
            "vatAmount" => [
                "currency" => "EUR",
                "value" => $shippingVat
            ]
        ];

        $order = $this->mollie->orders->create([
            "amount" => [
                "value" => number_format($cartValue, 2, '.', ''),
                "currency" => "EUR"
            ],
            "billingAddress" => [
                "streetAndNumber" => $_POST['streetAndNumber'],
                "city" => $_POST['city'],
                "postalCode" => $_POST['postalCode'],
                "country" => $_POST['country'],
                "givenName" => $_POST['givenName'],
                "familyName" => $_POST['familyName'],
                "email" => $_POST['email']
            ],
            "locale" => "nl_NL",
            "orderNumber" => "1337", //TODO: This is required but we don't store anything. How will we deal with this?
            "redirectUrl" => "https://netlipresscommerce.test/checkout",
            // "webhookUrl" => "https://example.org/webhook", //TODO: We need to research the workings of this for emails when a shipment is sent out
            "method" => "ideal", //TODO: Do we wanna support more?
            "lines" => $lines
        ]);
        $_SESSION['order_id'] = $order->id;
        header("Location: " . $order->getCheckoutUrl(), true, 303);
    }

    public function getOrder($id) {
        if(!empty($id)) {
            return $this->mollie->orders->get($id);
        }
    }

    public function handledPossibleOrderRedirect()
    {
        //When returning from Mollie the Session has the Order ID but the URL does not. We swap this and on a completed payment we also destroy the session (which empties the cart)
        if(isset($_SESSION['order_id']) && !isset($_GET['order'])) {
            $order = $this->getOrder($_SESSION['order_id']);
            header("Location: /checkout?order=" . $_SESSION['order_id'], true, 303);
            if($order->status === 'paid') {
                session_unset();
            }
        }
        //If we redirected already, GET has the order. We include partials to show notices in that case
        if (isset($_GET['order'])) {
            global $order;
            $order = $this->getOrder($_GET['order']);
            if ($order) {
                if ($order->status === 'paid') {
                    get_template_part('template-parts/commerce/order-complete');
                } else {
                    get_template_part('template-parts/commerce/order-failed');
                }
            } else {
                get_template_part('template-parts/commerce/order-not-found');
            }
            //We return false to signal to the template that the cart shouldn't be rendered
            return false;
        }
        //We return true to signal all possible redirect cases were checked but not present, so the template can continue rendering the cart
        return true;
    }
}
