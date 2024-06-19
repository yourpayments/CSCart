<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_payu_install()
{
    fn_payu_uninstall();

    $_data = array(
        'processor' => 'PayU',
        'processor_script' => 'payu.php',
        'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
        'admin_template' => 'payu.tpl',
        'callback' => 'Y',
        'type' => 'P',
        'addon' => 'payu'
    );

    db_query("INSERT INTO ?:payment_processors ?e", $_data);
}

function fn_payu_uninstall()
{
    db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s", "payu.php");
}


function fn_payu_make_string($name, $val)
{   
    if (!is_array($val)) 
        echo '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars($val).'">'."\n";
    else
        foreach ($val as $v) fn_payu_make_string($name, $v);
}
