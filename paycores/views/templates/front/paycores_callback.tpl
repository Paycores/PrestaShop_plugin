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
  {l s='There was an error with the request, please contact ' mod='paycores'}<a href="{$paycoresAdmin|escape:'htmlall':'UTF-8'}"><b>{l s='the website administrator' mod='paycores'}</b></a>

  <div class="clearfix"></div>
  <br>

  <h3>{l s='Error code' mod='paycores'}: {$paycoresError|escape:'htmlall':'UTF-8'}</h3>

  <div class="clearfix"></div>
  <br>

  <div class="col-sm-12">
    <a href="{$paycoresHome|escape:'htmlall':'UTF-8'}" class="btn btn-primary float-xs-right" >{l s='Continue' mod='paycores'}</a>
  </div>

  <div class="clearfix"></div>
  <br>
</section>
{/block}