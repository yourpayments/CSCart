<?php
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    return;
}

if ($mode == 'update' || $mode == 'manage') {

    $processors = Tygh::$app['view']->getTemplateVars('payment_processors');

    if (!empty($processors)) {

        foreach ($processors as &$processor) {
            if ($processor['processor'] == 'PayU') {
                $processor['russian'] = 'Y';
                $processor['type'] = 'R';
                $processor['position'] = 'a_30';
            }
        }
        $processors = fn_sort_array_by_key($processors, 'position');

        Tygh::$app['view']->assign('payment_processors', $processors);
    }

}

