{*
 * Paycores
 *
 * @author    Paycores
 * @copyright Copyright (c) 2017 Paycores
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * https://paycores.com
 *}

{extends file=$layout}

{block name='content'}

<section>
  <h6>{l s='Thank you for your order, continue with the following form and finalize your purchase with Paycores.' mod='paycores'}</h6>

  <div class="clearfix"></div>
  <br>

  <form action="{$paycoresUrl|escape:'htmlall':'UTF-8'}" method="post" class="form-horizontal">
    {foreach $paycores_args as $key => $value}
      {if empty($birthday) && $key == 'paycores_usr_birth'}
        <div class="col-sm-12">
          <label for="paycores_usr_birth">{l s='Birthday' mod='paycores'}</label>
          <input type="text" class="form-control" name="paycores_usr_birth" id="paycores_usr_birth" placeholder="1980-01-20" required>
        </div>
      {/if}
      <input type="hidden" name="{$key|escape:'htmlall':'UTF-8'}" value="{$value|escape:'htmlall':'UTF-8'}" />
    {/foreach}

    <div class="clearfix"></div>
    <br>

    <div class="col-sm-12">
      <label for="paycores_usr_numberId">{l s='Identification number' mod='paycores'}</label>
      <input type="text" class="form-control" name="paycores_usr_numberId" id="paycores_usr_numberId" placeholder="1094000000" required>
    </div>

    <div class="clearfix"></div>
    <br>

    <div class="col-sm-12">
      <label for="genlist">{l s='Gender' mod='paycores'}</label>
      <select name="paycores_usr_gender" id="genlist" class="form-control">
        <option value="M">{l s='Male' mod='paycores'}</option>
        <option value="F">{l s='Female' mod='paycores'}</option>
      </select>
    </div>

    <div class="clearfix"></div>
    <br>

    <div class="col-sm-12">
      <label for="paycores_usr_state">{l s='State' mod='paycores'}</label>
      <input type="text" class="form-control" name="paycores_usr_state" id="paycores_usr_state" placeholder="{l s='State' mod='paycores'}" required>
    </div>

    <div class="clearfix"></div>
    <br>

    <div class="col-sm-12">
      <input class="btn btn-primary float-xs-right" id="submit_paycores_payment_form" type="submit" value="{l s='Payment via Paycores' mod='paycores'}">
    </div>

    <div class="clearfix"></div>
    <br>
  </form>
</section>

{/block}