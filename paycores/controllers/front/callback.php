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

class PaycoresCallbackModuleFrontController extends ModuleFrontController
{

    private $paycoresID = 0;
    private $codeResponse = "";
    private $paycoresMessage = "";

    /**
     * Recibe peticion post
     *
     * @access public
     */
    public function postProcess()
    {
        $this->paycoresID = (int)Tools::getValue('paycores_order_id');
        $this->codeResponse = Tools::getValue('codeResponse');
        $this->paycoresMessage = Tools::getValue('message');
    }

    /**
     * Verifica el estado de las transacciones y redirecciona
     * a la vista correspondiente
     *
     * @access public
     */
    public function initContent()
    {
        parent::initContent();

        $link = new Link();
        $cart = new Cart($this->paycoresID);
        $customer = new Customer($cart->id_customer);
        $order = Order::getIdByCartId((int)($cart->id));

        switch ($this->codeResponse) {
            case '001':
                $success_url = $link->getPageLink('order-confirmation', null, null, array(
                    'id_cart'     => $this->paycoresID,
                    'id_module'   => $this->module->id,
                    'key'         => $customer->secure_key
                ));

                $history = new OrderHistory();
                $history->id_order = (int)$order;
                $history->changeIdOrderState((int)Configuration::get('PAYCORES_SUCCESS'), $history->id_order);
                $history->addWithemail();
                $history->save();

                Tools::redirect($success_url);
                break;
            default:
                $paycoresData = array();
                $paycoresData["paycoresError"]      = $this->codeResponse;
                $paycoresData["paycoresAdmin"]      = $link->getPageLink('contact-form.php', true);
                $paycoresData["paycoresHome"]       = $link->getPageLink('/', true);
                $paycoresData["paycoresMessage"]    = $this->paycoresMessage;

                $history = new OrderHistory();
                $history->id_order = (int)$order;
                $history->changeIdOrderState((int)Configuration::get('PAYCORES_DENIED'), $history->id_order);
                $history->addWithemail();
                $history->save();

                $this->context->cart->delete();
                $this->context->cookie->id_cart = 0;

                $this->context->smarty->assign(
                    $paycoresData
                );

                $this->paycoresError($this->paycoresMessage, $this->paycoresID);
                _PS_VERSION_ >= '1.7' ?
                    $this->setTemplate('module:paycores/views/templates/front/paycores_callback.tpl')
                    :
                    $this->setTemplate('paycores_callback.tpl');
                break;
        }
    }

    /**
     * Imprime un error en el log de registros de PrestaShop
     *
     * @access private
     * @param $message
     * @param $order_id
     */
    private function paycoresError($message, $order_id)
    {
        PrestaShopLogger::addLog($message, 3, null, 'Cart', $order_id, true);
    }
}
