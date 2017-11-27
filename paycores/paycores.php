<?php
/**
 * Created by Paycores.com.
 * User: paycores-02
 * Date: 15/11/17
 * Time: 09:34 AM
 */

require_once(_PS_MODULE_DIR_ . '/paycores/controllers/front/paycores_version.php');
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Paycores extends PaymentModule {

    public function __construct() {
        $this->name = 'paycores';
        $this->tab = 'payments_gateways';
        $this->version = PAYCORES_VERSION;
        $this->author = 'Paycores.com';
        $this->is_eu_compatible = 1;
        $this->controllers = array('redirect', 'callback');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        $config = Configuration::getMultiple(
            array(
                'PAYCORES_API_KEY',
                'PAYCORES_API_LOGIN',
                'PAYCORES_COMMERCE_ID',
                'PAYCORES_TEST_MODE'
            )
        );

        if (!empty($config['PAYCORES_API_KEY'])) {
            $this->api_key = $config['PAYCORES_API_KEY'];
        }

        if (!empty($config['PAYCORES_API_LOGIN'])) {
            $this->app_id = $config['PAYCORES_API_LOGIN'];
        }

        if (!empty($config['PAYCORES_COMMERCE_ID'])) {
            $this->api_secret = $config['PAYCORES_COMMERCE_ID'];
        }

        if (!empty($config['PAYCORES_TEST_MODE'])) {
            $this->test = $config['PAYCORES_TEST_MODE'];
        }

        parent::__construct();

        $this->displayName = $this->l('Paycores Payment Gateway');
        $this->description = $this->l('Accept credit card via Paycores');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (!isset($this->app_id)
            || !isset($this->api_key)
            || !isset($this->api_secret)
            || !isset($this->receive_currency)) {
            $this->warning = $this->l('API Access details must be configured in order to use this modules correctly.');
        }
    }

    /**
     * Crea los estados de orden
     *
     * @access public
     * @return bool
     */
    public function install() {
        $order_success = new OrderState();
        $order_success->name = array_fill(0, 10, $this->l('Payment approved by Paycores'));
        $order_success->send_email = 1;
        $order_success->invoice = 1;
        $order_success->color = '#32CD32';
        $order_success->unremovable = false;
        $order_success->logable = 1;
        $order_success->template = "payment";

        $order_pending = new OrderState();
        $order_pending->name = array_fill(0, 10, $this->l('Awaiting Paycores payment'));
        $order_pending->send_email = 0;
        $order_pending->invoice = 0;
        $order_pending->color = '#008dd2';
        $order_pending->unremovable = false;
        $order_pending->logable = 0;

        $order_denied = new OrderState();
        $order_denied->name = array_fill(0, 10, $this->l('Order denied by Paycores'));
        $order_denied->send_email = 0;
        $order_denied->invoice = 0;
        $order_denied->color = '#8f0621';
        $order_denied->unremovable = false;
        $order_denied->logable = 0;

        if ($order_success->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/paycores/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int)$order_success->id . '.gif'
            );
        }

        if ($order_pending->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/paycores/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int)$order_pending->id . '.gif'
            );
        }

        if ($order_denied->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/paycores/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int)$order_denied->id . '.gif'
            );
        }

        Configuration::updateValue('PAYCORES_SUCCESS', $order_success->id);
        Configuration::updateValue('PAYCORES_PENDING', $order_pending->id);
        Configuration::updateValue('PAYCORES_DENIED', $order_denied->id);


        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')) {
            return false;
        }

        return true;
    }

    /**
     * Elimina los estados de orden
     *
     * @access public
     * @return bool
     */
    public function uninstall() {
        $order_success = new OrderState(Configuration::get('PAYCORES_SUCCESS'));
        $order_pending = new OrderState(Configuration::get('PAYCORES_PENDING'));
        $order_denied = new OrderState(Configuration::get('PAYCORES_DENIED'));

        return (
            Configuration::deleteByName('PAYCORES_API_LOGIN') &&
            Configuration::deleteByName('PAYCORES_API_KEY') &&
            Configuration::deleteByName('PAYCORES_COMMERCE_ID') &&
            Configuration::deleteByName('PAYCORES_TEST_MODE') &&
            $order_success->delete() &&
            $order_pending->delete() &&
            $order_denied->delete() &&
            parent::uninstall()
        );
    }

    /**
     * Valida que se encuentren todos los datos de configuracion
     *
     * @access private
     */
    private function postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('PAYCORES_API_KEY')) {
                $this->postErrors[] = $this->l('API Key is required.');
            }

            if (!Tools::getValue('PAYCORES_API_LOGIN')) {
                $this->postErrors[] = $this->l('API Login is required.');
            }

            if (!Tools::getValue('PAYCORES_COMMERCE_ID')) {
                $this->postErrors[] = $this->l('CommerceID is required.');
            }
        }
    }

    /**
     * Recibe peticion post
     *
     * @access private
     */
    private function postProcess() {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PAYCORES_API_KEY', $this->stripString(Tools::getValue('PAYCORES_API_KEY')));
            Configuration::updateValue('PAYCORES_API_LOGIN', $this->stripString(Tools::getValue('PAYCORES_API_LOGIN')));
            Configuration::updateValue('PAYCORES_COMMERCE_ID', $this->stripString(Tools::getValue('PAYCORES_COMMERCE_ID')));
            Configuration::updateValue('PAYCORES_TEST_MODE', Tools::getValue('PAYCORES_TEST_MODE'));
        }

        $this->html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * Muesta una vista en la configuracion del Paycores
     *
     * @access private
     * @return mixed
     */
    private function displayPaycores() {
        return $this->display(__FILE__, 'paycores_contact.tpl');
    }

    /**
     * Genera la vista con todos los datos
     * de configuracion de Paycores
     *
     * @access public
     * @return string
     */
    public function getContent() {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) {
                    $this->html .= $this->displayError($err);
                }
            }
        } else {
            $this->html .= '<br />';
        }

        $this->html .= $this->displayPaycores();
        $this->html .= $this->renderForm();

        return $this->html;
    }

    /**
     * Verifica que el modulo se encuentre activo
     * y que hayan datos en el carrito de compras
     *
     * @access public
     * @param $params
     */
    public function hookPayment($params) {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(array(
            'this_path'     => $this->_path,
            'this_path_bw'  => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));

        return $this->display(__FILE__, 'paycores_payment.tpl');
    }

    /**
     * Genera la vista de Paycores para llenar los datos
     * faltantes para la correcta redireccion al checkout
     *
     * @access public
     * @param $params
     * @return array|void
     */
    public function hookPaymentOptions($params) {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $paycoresOption = new PaymentOption();
        $paycoresOption->setCallToActionText($this->l(''))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ .$this->name.'/views/templates/img/paycores_small.png'))
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true))
            ->setAdditionalInformation($this->fetch('module:'.$this->name.'/views/templates/hook/paycores_intro.tpl'));

        return array($paycoresOption);
    }

    /**
     * verfica el tipo de moneda
     *
     * @access public
     * @param $cart
     * @return bool
     */
    public function checkCurrency($cart) {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Crea el formulario para la configuracion
     * de las variables de Paycores
     *
     * @access public
     * @return mixed
     */
    public function renderForm() {
        $PAYCORES_CLASS = "";
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Paycores Payment Gateway'),
                    'icon'  => 'icon-cog'
                ),
                'input'  => array(
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('API Key'),
                        'name'     => 'PAYCORES_API_KEY',
                        'desc'     => $this->l('Your Api Key.'),
                        'required' => true,
                        'class'    => $PAYCORES_CLASS
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Api Login'),
                        'name'     => 'PAYCORES_API_LOGIN',
                        'desc'     => $this->l('Your Api Login.'),
                        'required' => true,
                        'class'    => $PAYCORES_CLASS
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('CommerceID'),
                        'name'     => 'PAYCORES_COMMERCE_ID',
                        'desc'     => $this->l('Your CommerceID.'),
                        'required' => true,
                        'class'    => $PAYCORES_CLASS
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Test Mode'),
                        'name'     => 'PAYCORES_TEST_MODE',
                        'desc'     => $this->l('Activate test transactions.'),
                        'required' => true,
                        'class'    => $PAYCORES_CLASS,
                        'options'  => array(
                            'query' => array(
                                array(
                                    'id_option' => 2,
                                    'name'      => 'Off'
                                ),
                                array(
                                    'id_option' => 1,
                                    'name'      => 'On'
                                ),
                            ),
                            'id'    => 'id_option',
                            'name'  => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0);
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module='
            . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Consulta y muestra el estado anterior de las variables
     *
     * @access public
     * @return array
     */
    public function getConfigFieldsValues() {
        return array(
            'PAYCORES_API_LOGIN' => $this->stripString(Tools::getValue(
                'PAYCORES_API_LOGIN',
                Configuration::get('PAYCORES_API_LOGIN')
            )),
            'PAYCORES_API_KEY' => $this->stripString(Tools::getValue(
                'PAYCORES_API_KEY',
                Configuration::get('PAYCORES_API_KEY')
            )),
            'PAYCORES_COMMERCE_ID' => $this->stripString(Tools::getValue(
                'PAYCORES_COMMERCE_ID',
                Configuration::get('PAYCORES_COMMERCE_ID')
            )),
            'PAYCORES_TEST_MODE' => Tools::getValue(
                'PAYCORES_TEST_MODE',
                Configuration::get('PAYCORES_TEST_MODE')
            ),
        );
    }

    private function stripString($item) {
        return preg_replace('/\s+/', '', $item);
    }
}