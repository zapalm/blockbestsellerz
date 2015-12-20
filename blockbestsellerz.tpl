<!-- MODULE Block best sellers -->
<div id="viewed-products_block_left" class="block products_block">
	<h4><a href="{$base_dir}best-sales.php">{l s='Top sellers' mod='blockbestsellerz'}</a></h4>
	<div class="block_content">
	{if $best_sellers != false}    
		<ul class="products clearfix">
		{foreach from=$best_sellers item=product name=myLoop}
			<li class="clearfix{if $smarty.foreach.myLoop.last} last_item{elseif $smarty.foreach.myLoop.first} first_item{else} item{/if}">
				<a href="{$product.link}" title="{$product.legend|escape:html:'UTF-8'}"><img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'medium')}" height="{$mediumSize.height}" width="{$mediumSize.width}" alt="{$product.legend|escape:html:'UTF-8'}" /></a>
				<h5><a href="{$product.link}" title="{$product.name|escape:html:'UTF-8'}">{$product.name|escape:html:'UTF-8'|truncate:14:'...'}</a></h5>
				<p><a href="{$product.link}">{$product.description_short|strip_tags:'UTF-8'|truncate:50:'...'}</a></p>
			</li>
		{/foreach}
		</ul>
		<p><a href="{$base_dir}best-sales.php" title="{l s='All best sellers' mod='blockbestsellerz'}" class="button_large">{l s='All best sellers' mod='blockbestsellerz'}</a></p>
	{else}
		<p>{l s='No best sellers at this time' mod='blockbestsellerz'}</p>
	{/if}
	</div>
</div>
<!-- /MODULE Block best sellers -->
