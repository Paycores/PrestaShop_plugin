{*
 * Created by Paycores.com.
 * User: paycores-02
 * Date: 15/11/17
 * Time: 09:59 AM
 *}

{extends file=$layout}

{block name='content'}

<section>
  {l s='There was an error with the request, please contact the website '}<a href="{$paycoresAdmin}"><b>{l s= 'administrator'}</b></a>

  <div class="clearfix"></div>
  <br>

  <h3>{l s='Error code'}: {$paycoresError}</h3>

  <div class="clearfix"></div>
  <br>

  <div class="col-sm-12">
    <a href="{$paycoresHome}" class="btn btn-primary float-xs-right" >{l s='Continue'}</a>
  </div>

  <div class="clearfix"></div>
  <br>
</section>
{/block}