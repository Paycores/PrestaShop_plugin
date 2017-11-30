<?php
/**
 * Paycores
 *
 * @author    Paycores
 * @copyright Copyright (c) 2017 Paycores
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * https://paycores.com
 */

class PaycoresRedirectModuleFrontController extends ModuleFrontController
{

    /**
     * Recibe peticion post
     *
     * @access public
     */
    public function postProcess()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == "paycores") {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }

        if (!$this->module->active) {
            die($this->module->l('Paycores module isn\'t active.', 'payment'));
        }
    }

    /**
     * Crea la orden e inicializa el estado de ésta,
     * ademas de crear los parametros necesarios para redireccionar
     * al Checkout de Paycores
     *
     * @access public
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $paycoresAddress = new AddressCore($cart->id_address_invoice);
        $paycoresCountry = new CountryCore($paycoresAddress->id_country);
        $paycoresCurrency = new CurrencyCore($this->context->cookie->id_currency);

        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $paycoresAmount = (float)number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
        $paycoresDecimal = explode(".", $paycoresAmount);

        if (count($paycoresDecimal) > 1) {
            if (Tools::strlen($paycoresDecimal[1]) < 2) {
                $paycoresAmount = $paycoresAmount."0";
            } elseif (Tools::strlen($paycoresDecimal[1]) < 1) {
                $paycoresAmount = $paycoresAmount."00";
            }
        } else {
            $paycoresAmount = $paycoresAmount.".00";
        }

        $description = array();
        foreach ($cart->getProducts() as $product) {
            $description[] = $product['cart_quantity'] . ' × ' . $product['name'];
        }

        $customer = new Customer($cart->id_customer);

        $link = new Link();

        $redirectLink = $link->getModuleLink("paycores", "callback");

        $paycores = "paycores";
        $birthday = $customer->birthday;

        $paycoresDescription = join($description, ', ');
        $usrAddress = $paycoresAddress->address1 . " ". $paycoresAddress->address2;

        if (Configuration::get('PAYCORES_TEST_MODE') == '1') {
            $paycoresUrl = 'https://sandbox.paycores.com/web-checkout/';
        } elseif (Configuration::get('PAYCORES_TEST_MODE') == '2') {
            $paycoresUrl = 'https://business.paycores.com/web-checkout/';
        } else {
            $paycoresUrl = null;
        }

        $paycores_args = array(
            $paycores.'_is_ecommerce'       => true,
            $paycores.'_type_ecommerce'     => $paycores."PrestaShop",
            $paycores.'_access_key'         => Configuration::get('PAYCORES_API_KEY'),
            $paycores.'_access_login'       => Configuration::get('PAYCORES_API_LOGIN'),
            $paycores.'_access_commerceid'  => Configuration::get('PAYCORES_COMMERCE_ID'),
            $paycores.'_test'               => Configuration::get('PAYCORES_TEST_MODE'),
            $paycores.'_amount'             => $paycoresAmount,
            $paycores.'_currency'           => $paycoresCurrency->iso_code,
            $paycores.'_usr_name'           => $paycoresAddress->firstname,
            $paycores.'_usr_lname'          => $paycoresAddress->lastname,
            $paycores.'_usr_birth'          => $birthday,
            $paycores.'_usr_email'          => $customer->email,
            $paycores.'_usr_phone'          => $paycoresAddress->phone,
            $paycores.'_usr_cellphone'      => $paycoresAddress->phone,
            $paycores.'_usr_address'        => Tools::substr($usrAddress, 0, 40).
                (Tools::strlen($usrAddress)>40 ? '...' : ""),
            $paycores.'_usr_city'           => $paycoresAddress->city,
            $paycores.'_usr_country_ad'     => $paycoresCountry->iso_code,
            $paycores.'_usr_nation'         => $paycoresCountry->iso_code,
            $paycores.'_usr_postal_code'    => $paycoresAddress->postcode,
            $paycores.'_description'        => Tools::substr($paycoresDescription, 0, 25).
                (Tools::strlen($paycoresDescription)>25 ? '...' : ""),
            $paycores.'_gd_name'            => Tools::substr($paycoresDescription, 0, 16).
                (Tools::strlen($paycoresDescription)>16 ? '...' : ""),
            $paycores.'_gd_descript'        => Tools::substr($paycoresDescription, 0, 16).
                (Tools::strlen($paycoresDescription)>16 ? '...' : ""),
            $paycores.'_gd_quantity'        => count($cart->getProducts()),
            $paycores.'_gd_item'            => (int)$paycoresAmount,
            $paycores.'_gd_code'            => (int)$paycoresAmount,
            $paycores.'_gd_amount'          => $paycoresAmount,
            $paycores.'_gd_unitPrice'       => $paycoresAmount,
            $paycores.'_tax'                => "0.00",
            $paycores.'_tax_ret'            => "0.00",
            $paycores.'_extra1'             => $cart->id,
            $paycores.'_order_id'           => $cart->id,
            $paycores.'_response_url'       => $redirectLink,
            $paycores.'_confirmation_url'   => $redirectLink
        );

        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PAYCORES_PENDING'),
            $paycoresAmount,
            $this->module->displayName,
            null,
            null,
            (int)$paycoresCurrency->id,
            false,
            $customer->secure_key
        );

        $paycoresData = array();
        $paycoresData["paycores_args"]      = $paycores_args;
        $paycoresData["paycores_amount"]    = $paycoresAmount;
        $paycoresData["paycoresDescript"]   = $paycoresDescription;
        $paycoresData["paycoresCurrency"]   = $paycoresCurrency->iso_code;
        $paycoresData["birthday"]           = $birthday;
        $paycoresData["paycoresUrl"]        = $paycoresUrl;

        $this->context->smarty->assign(
            $paycoresData
        );

        $this->setTemplate('module:paycores/views/templates/front/paycores_redirect.tpl');
    }
}
