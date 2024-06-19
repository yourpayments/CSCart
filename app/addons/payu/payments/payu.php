<?php
use Tygh\Payments\Processors\PayU;
use Tygh\Enum\OrderDataTypes;
use Tygh\Embedded;

$tax_rates = array(
    20 => 19,
    10 => 10,
    0 => 0
);

if (defined('PAYMENT_NOTIFICATION')) {

    $order_id = 0;
    if (!empty($_REQUEST['ordernumber'])) {
        $order_id = $_REQUEST['ordernumber'];
    } else {
        $order_id = $_REQUEST['REFNOEXT'];
    }

    $order_info = fn_get_order_info($order_id);
    if (empty($processor_data) && !empty($order_info)) {
        $processor_data = fn_get_processor_data($order_info['payment_id']);
    }

    if (!empty($processor_data['processor_params']['logging']) &&
        $processor_data['processor_params']['logging'] == 'Y') {
        PayU::writeLog($_REQUEST, 'payu_request.log');
    }

    if (!empty($order_info) && ($mode == 'return' || $mode == 'callback')) {

        $pp_response = array(
            'order_status' => $processor_data['processor_params']['statuses']['error']
        );

        if($mode == 'callback') {
            $payu = new PayU(
                '', 
                $processor_data['processor_params']['merchant_name'],
                $processor_data['processor_params']['secret_key'],
                'TRUE'
            );

            if ($_POST['HASH'] == $payu->calculateIPNHash()) {
                if ($_POST['IPN_TOTALGENERAL'] == $order_info['total']) {
                    if (
                        $_POST['ORDERSTATUS'] == 'PAYMENT_AUTHORIZED' ||
                        $_POST['ORDERSTATUS'] == '-' ||
                        ($_POST['ORDERSTATUS'] == 'TEST' && 
                            $processor_data['processor_params']['mode'] == 'test')
                    ) {
                        $pp_response = array(
                            'order_status' => 
                                $processor_data['processor_params']['statuses']['paid'],
                            'reason_text' => __("addons.payu.success_withdrawn")
                        );
                    }
                } else {
                    $pp_response['reason_text'] = __("addons.payu.wrong_amount");
                }
            } else {
                $pp_response = array(
                    'order_status' => 
                        $processor_data['processor_params']['statuses']['error'],
                    'reason_text' => __("addons.payu.wrong_hash")
                );
            }
            echo $payu->handleIpnRequest();
            fn_finish_payment($order_id, $pp_response, true);  
        } else {
            $pp_response = array(
                'order_status' => 'B'
            );
            if (isset($_GET['err'])) {
                $pp_response = array(
                    'order_status' => 'F',
                    'reason_text' => $_GET['err']
                );  
            }
            fn_finish_payment($order_id, $pp_response, true);
        }
        PayU::writeLog($pp_response, 'payu_request.log');
        fn_order_placement_routines('route', $order_id, false);
    }
    exit;
} else {
    $payu = new PayU(
        '', 
        $processor_data['processor_params']['merchant_name'],
        $processor_data['processor_params']['secret_key']
    );

    $order_pnames = array();
    $order_pcodes = array();
    $order_prices = array();
    $order_qtys = array();
    $order_vats = array();
    $order_price_type = array();
    $discount = $order_info['subtotal_discount'];
    $coefSum = 0;

    $len = count($order_info['products']);
    $i = 1;
    foreach ($order_info['products'] as $product) {
        $price = $product['price'];
        if ($discount) {
            if ($i != $len || $order_info['shipping_cost']) {
                $coef = round(($product['price'] * $product['amount']) / 
                    ($order_info['subtotal'] + $order_info['shipping_cost']), 2);
            } elseif (!$order_info['shipping_cost'] && $i == $len) {
                $coef = 1 - $coefSum;
            }
            $price = round((($product['price'] * $product['amount']) - $coef * $discount) / 
                $product['amount']);
            $coefSum += $coef;
        }
        $order_pnames[] = $product['product'];
        $order_pcodes[] = $product['product_code'] ? 
            $product['product_code'] : $product['product_id'];
        $order_prices[] = $price;
        $order_qtys[] = $product['amount'];
        $productTax = 0;
        foreach ($order_info['taxes'] as $tax) {
            $productTax = -1;
            if (in_array(
                $product['item_id'], 
                array_keys($tax['applies']['items']['P']
                ))
            ) {
                $productTax = round($tax['rate_value']);
                break;
            }
        }
        $order_vats[] = $productTax == -1 ? $processor_data['processor_params']['tax'] :
            $tax_rates[$productTax];

        if ($tax['price_includes_tax'] == 'Y'){
            $order_price_type[] = 'GROSS';
        } else {
            $order_price_type[] = 'NET';
        }
        $i++;
    }
    if (isset($order_info['shipping'][0]) && $order_info['shipping_cost'] > 0) {
        $price = $order_info['shipping_cost'];
        if ($discount) {
            $coef = 1 - $coefSum;
            $price = round($order_info['shipping_cost'] - ($coef * $discount));
        }
        $order_pnames[] = $order_info['shipping'][0]['shipping'];
        $order_pcodes[] = $order_info['shipping'][0]['shipping_id'];
        $order_prices[] = $price;
        $order_qtys[] = 1;
        $order_vats[] = 0;
        $order_price_type[] = 'NET';
    }

    $formData = $payu->initLiveUpdateFormData(array(
        
        // Данные заказа
        'ORDER_REF' => $order_info['order_id'],
        'ORDER_DATE' => date('Y-m-d H:i:s'),
        'ORDER_PNAME[]' => $order_pnames,
        'ORDER_PCODE[]' => $order_pcodes,
        'ORDER_PRICE[]' => $order_prices,
        'ORDER_QTY[]' => $order_qtys,
        'ORDER_VAT[]' => $order_vats,
        //'ORDER_SHIPPING' => $order_info['shipping_cost'],
        'PRICES_CURRENCY' => 'RUB',
        //'DISCOUNT' => $order_info['discount'],
        'AUTOMODE' => 1,

        // Данные плательщика
        'BILL_FNAME' => $order_info['b_firstname'],
        'BILL_LNAME' => $order_info['b_lastname'],
        'BILL_EMAIL' => $order_info['email'],
        'BILL_PHONE' => $order_info['b_phone'],
        'BILL_ADDRESS' => $order_info['b_address'],
        'BILL_CITY' => $order_info['b_city'],

        // Данные получателя
        'DELIVERY_FNAME' => $order_info['s_firstname'],
        'DELIVERY_LNAME' => $order_info['s_lastname'],
        'DELIVERY_PHONE' => $order_info['s_phone'],
        'DELIVERY_ADDRESS' => $order_info['s_address'],
        'DELIVERY_CITY' => $order_info['s_city'],
        'ORDER_PRICE_TYPE[]' => $order_price_type,
        'TESTORDER' => 
            $processor_data['processor_params']['mode'] == 'test' ? 'TRUE' : 'FALSE',

    ), fn_url('payment_notification.return&payment=payu&ordernumber='.$order_info['order_id'], AREA, 'current'));
    
    if (!empty($processor_data['processor_params']['logging']) && 
        $processor_data['processor_params']['logging'] == 'Y'
    ) {
        PayU::writeLog($formData, 'payu_request.log');
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Оплата заказа</title>
</head>
<body>
    <p><?php echo __("addons.payu.text_redirect"); ?></p>
    <form style="display: none;" action="<?php echo PayU::LU_URL; ?>" 
        method="post" name="process">
    <?php
        foreach ($formData as $formDataKey => $formDataValue)
            fn_payu_make_string($formDataKey, $formDataValue);
    ?>
	<input type="submit" value="send" />
</form>
<script type="text/javascript">
    window.onload = function(){
       document.process.submit();
    };
</script>
</body>
</html>

<?php
exit;
}
