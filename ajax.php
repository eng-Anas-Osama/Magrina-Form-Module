<?php
require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/customcheckout.php');

$module = new CustomCheckout();

if (Tools::getValue('action') == 'getStates') {
    $governmentId = Tools::getValue('government_id');
    $states = $module->getStatesByGovernment($governmentId);
    die(json_encode($states));
}