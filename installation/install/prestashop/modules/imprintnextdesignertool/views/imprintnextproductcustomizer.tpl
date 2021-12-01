<div class="panel">
	<div class="imprintnextproductcustomizer" style="padding:20px;">
		<table>
			<tr>
				<td class="col-left" valign="top">
					<label>{l s='Product Customization:'}</label>
				</td>
				<td>
					<div style="padding:0px 20px;">
				  	<input type="radio" name="custom_field" value="1" {if $custom_field == '1'}checked="checked"{/if}> <span style="padding:0px 10px;">Yes</span><br/>
				  	<input type="radio" name="custom_field" value="0" {if $custom_field == '0'}checked="checked"{/if}> <span style="padding:0px 10px;">No</span>
				  </div>
				</td>
			</tr>
		</table>
	</div>
</div>