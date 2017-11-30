{*
 * Paycores
 *
 * @author    Paycores
 * @copyright Copyright (c) 2017 Paycores
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * https://paycores.com
 *}

<div class="row">
  <div class="col-xs-12">
    <p class="payment_module">
      <a style="background-image: url('{$this_path|escape:'htmlall':'UTF-8'}views/img/paycores.png'); padding-left:150px;  background-size: 100px; background-position: 20px; 50%; background-repeat: no-repeat;" href="{$link->getModuleLink('paycores', 'payment')|escape:'htmlall':'UTF-8'}">
        {l s='Pay with credit card via Paycores.com' mod='paycores'}
        <br><span>({l s='Now you can easily pay or receive payments' mod='paycores'})</span>
      </a>
    </p>
  </div>
</div>

