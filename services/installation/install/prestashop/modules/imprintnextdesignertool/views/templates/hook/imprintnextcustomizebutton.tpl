<!-- Block imprintnextcustomizebutton -->
{if isset($product_id) || $product_id}
	<script type="text/javascript">
		var product_id = '{$product_id|escape:'html':'UTF-8'}';
		var xeStoreUrl = '{$xeStoreUrl|escape:'html':'UTF-8'}';
		var apiKey = '{$apiKey|escape:'html':'UTF-8'}';
		var  is_addtocart = '{$is_addtocart|escape:'html':'UTF-8'}';
		var  cms_page = '{$cms_page|escape:'html':'UTF-8'}';
		var  xe_is_temp = '{$xe_is_temp|escape}';
		var  p_category = '{$p_category|escape}';
		var  storeId = '{$store|escape}';
	</script>
{/if}
<!--{if (isset($is_addtocart) &&  $is_addtocart==0) && (isset($xe_is_temp) &&  $xe_is_temp==1)}
<div id="imprintnextaddtocartbutton_block_home" class="box-cart-bottom">
	<div>
		<p class="buttons_bottom_block no-print">
			<button type="button" name="custom-add-tocart" class="btn btn-primary add-to-cart" onclick="addTemplateToCart();">
				<span>Add to cart</span>
			</button>
		</p>
	</div>
</div>
{/if}
-->
{if (isset($is_customize) &&  $is_customize==1)}
<div id="imprintnextcustomizebutton_block_home">
  <div class="block_content">
    <div class="customize_outer_div">
      <button type="button" name="customize" id="customize" class="btn btn-primary add-to-cart" value="Customize" class="customize_div" onclick="customize_product();">Customize</button>
    </div>
  </div>
</div>
{/if}
<!-- /Block imprintnextcustomizebutton -->