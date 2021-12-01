<!--
    Block imprintnextshoppingcart
-->
{if isset($cart_id) || $cart_id}
	<script type="text/javascript">
		var cms_page = '{$cms_page|escape:'html':'UTF-8'}';
		var cart_id = '{$cart_id|escape:'html':'UTF-8'}';
		var xeStoreUrl = '{$xeStoreUrl|escape:'html':'UTF-8'}';
		var storeId = '{$store|escape:'html':'UTF-8'}';
		var products = new Array();
	{foreach $products as $product}
		products.push({$product.id_product});
	{/foreach}
	</script>
{/if}

<!-- /Block imprintnextshoppingcart -->
