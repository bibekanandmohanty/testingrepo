{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

{if (isset($product.xe_is_temp) && $product.xe_is_temp) && (!$product.is_default_attribute) && ($product.product_type =='configurable')}
<div class="modal fade js-product-images-modal" id="product-modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body">
        {assign var=imagesCount value=$product.images|count}
        <figure>
            <div class="product-image product-image-zoom" style=" position: relative;display: block;border: 1px solid #ededed;background: #fff;">
                <div style="z-index: 0;font-size: 26px;position: relative;">                
                    <div style="display: block;position: absolute;top: 23%;left: 20%;-webkit-transform: scale3d(1.2, 1.2, 1.2);-moz-transform: scale3d(1.2, 1.2, 1.2);-o-transform: scale3d(1.2, 1.2, 1.2);-s-transform: scale3d(1.2, 1.2, 1.2);z-index: 9;">
                        <svg xmlns="http://www.w3.org/2000/svg" id="svgroot" xlinkns="http://www.w3.org/1999/xlink" width="500" height="500" x="0" y="0" overflow="visible">
                            <image  class ="predeco-image" height="{$product.cover.bySize.predeco.height}" width="{$product.cover.bySize.predeco.width}" 
                            y="{$product.cover.bySize.predeco.y}" x="{$product.cover.bySize.predeco.x}" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="{$product.cover.bySize.large_default.predecourl}" href="{$product.cover.bySize.large_default.predecourl}"></image>
                        </svg>
                    </div>        
                    <img class="js-modal-product-cover product-cover-modal" width="{$product.cover.large.width}" src="{$product.cover.large.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" itemprop="image"> 
                </div>
            </div>
            <figcaption class="image-caption">
              {block name='product_description_short'}
                <div id="product-description-short" itemprop="description">{$product.description_short nofilter}</div>
              {/block}
            </figcaption>
        </figure>
        <aside id="thumbnails" class="thumbnails js-thumbnails text-sm-center">
          {block name='product_images'}
            <div class="js-modal-mask mask {if $imagesCount <= 5} nomargin {/if}">
              <ul class="product-images js-modal-product-images">
                {foreach from=$product.images item=image}
                  <li class="thumb-container" style="display: inline;position: relative; vertical-align: top;">
                    <img  onclick="changePredecoImage({if !($product.is_default_attribute)}'{$image.bySize.home_default.predecourl}'{/if})"
                    {if !($product.is_default_attribute)} data-predeco-url="{$image.bySize.home_default.predecourl}" {/if} 
                    data-image-large-src="{$image.large.url}" class="thumb js-modal-thumb" src="{$image.medium.url}" alt="{$image.legend}" title="{$image.legend}" width="{$image.medium.width}" itemprop="image">
                    {if !empty($image.bySize.home_default.predecourl)}
                        <svg style="position: absolute;left: -50px; top: -45px;height: 75px!important;width: 75px!important;z-index:9999; overflow: visible;-webkit-transform: scale3d(0.4, 0.4, 0.4);-o-transform: scale3d(0.4, 0.4, 0.4);-s-transform: scale3d(0.4, 0.4, 0.4);z-index: 999;" xmlns="http://www.w3.org/2000/svg" id="svgroot" xlinkns="http://www.w3.org/1999/xlink" width="500" height="500" x="0" y="0" overflow="visible">
                            <image height="{$image.bySize.large_default.height}" width="{$image.bySize.large_default.width}" y="{$image.bySize.large_default.y}"
                         x="{$image.bySize.large_default.x}" id="layer_area_0" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="{$image.bySize.home_default.predecourl}" href="{$image.bySize.home_default.predecourl}"></image>
                        </svg>
                    {/if}
                  </li>
                {/foreach}
              </ul>
            </div>
          {/block}
          {if $imagesCount > 5}
            <div class="arrows js-modal-arrows">
              <i class="material-icons arrow-up js-modal-arrow-up">&#xE5C7;</i>
              <i class="material-icons arrow-down js-modal-arrow-down">&#xE5C5;</i>
            </div>
          {/if}
        </aside>
      </div>
    </div>
  </div>
</div>
{else}
<div class="modal fade js-product-images-modal" id="product-modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body">
        {assign var=imagesCount value=$product.images|count}
        <figure>
          <img class="js-modal-product-cover product-cover-modal" width="{$product.cover.large.width}" src="{$product.cover.large.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" itemprop="image">
          <figcaption class="image-caption">
          {block name='product_description_short'}
            <div id="product-description-short" itemprop="description">{$product.description_short nofilter}</div>
          {/block}
        </figcaption>
        </figure>
        <aside id="thumbnails" class="thumbnails js-thumbnails text-sm-center">
          {block name='product_images'}
            <div class="js-modal-mask mask {if $imagesCount <= 5} nomargin {/if}">
              <ul class="product-images js-modal-product-images">
                {foreach from=$product.images item=image}
                  <li class="thumb-container">
                    <img data-image-large-src="{$image.large.url}" class="thumb js-modal-thumb" src="{$image.medium.url}" alt="{$image.legend}" title="{$image.legend}" width="{$image.medium.width}" itemprop="image">
                  </li>
                {/foreach}
              </ul>
            </div>
          {/block}
          {if $imagesCount > 5}
            <div class="arrows js-modal-arrows">
              <i class="material-icons arrow-up js-modal-arrow-up">&#xE5C7;</i>
              <i class="material-icons arrow-down js-modal-arrow-down">&#xE5C5;</i>
            </div>
          {/if}
        </aside>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

{/if}
