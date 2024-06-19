{assign var="r_url" value="payment_notification.callback?payment=payu"|fn_url:'C':'http'}
<div>
    {__("addons.payu.text_payu_notice", ["[r_url]" => $r_url])}
</div> 
<hr>

<div class="control-group">
    <label class="control-label" for="merchant_name">{__("addons.payu.merchant_name")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_name]" id="merchant_name" value="{$processor_params.merchant_name}"  size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="secret_key">{__("addons.payu.secret_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][secret_key]" id="secret_key" value="{$processor_params.secret_key}"  size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mode">{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]" id="mode">
            <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
            <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mode">{__("addons.payu.tax")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][tax]" id="mode">
            <option value="0" {if $processor_params.tax == "0"}selected="selected"{/if}>{__("addons.payu.tax_0")}</option>
            <option value="10" {if $processor_params.tax == "10"}selected="selected"{/if}>{__("addons.payu.tax_10")}</option>
            <option value="19" {if $processor_params.tax == "19"}selected="selected"{/if}>{__("addons.payu.tax_20")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="logging">{__("addons.payu.logging")}:</label>
    <div class="controls">
        <input type="checkbox" name="payment_data[processor_params][logging]" id="logging" value="Y" {if $processor_params.logging == 'Y'} checked="checked"{/if}/>  
    </div>
</div>
<div>
    <small>
        Расположение лог-файла: 
        {''|fn_get_files_dir_path}payu_request.log 
    </small>
</div>

{include file="common/subheader.tpl" title=__("addons.payu.statuses") target="#text_status_map"}

<div id="text_status_map" class="in collapse">
    {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
    <div class="control-group">
        <label class="control-label" for="elm_paid">{__("addons.payu.paid")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][paid]" id="elm_paid">
                {foreach from=$statuses item="s" key="k"}
                    <option value="{$k}" {if (isset($processor_params.statuses.paid) && $processor_params.statuses.paid == $k) || (!isset($processor_params.statuses.paid) && $k == 'P')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

<div id="text_status_map" class="in collapse">
    {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
    <div class="control-group">
        <label class="control-label" for="elm_error">{__("addons.payu.error")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][error]" id="elm_error">
                {foreach from=$statuses item="s" key="k"}
                    <option value="{$k}" {if (isset($processor_params.statuses.error) && $processor_params.statuses.error == $k) || (!isset($processor_params.statuses.error) && $k == 'P')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

