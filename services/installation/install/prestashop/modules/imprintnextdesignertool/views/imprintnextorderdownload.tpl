<!--
    Block imprintnextorderdownload
-->
{addJsDef order_id=$order_id|escape:'html':'UTF-8'}
{addJsDef xeStoreUrl=$xeStoreUrl|escape:'html':'UTF-8'}
<div id="downloadDiv" style="display:none;">
    <button class="btn btn-success" name="download_order" type="button" onclick="order_download();">
        Download Order
    </button>
</div>
<!-- /Block imprintnextorderdownload -->
