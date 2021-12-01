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
<div class="images-container">
    {if (isset($products.xe_is_temp) && $products.xe_is_temp) && (!$products.is_default_attribute) && ($products.product_type =='configurable')}
    {block name='products_cover'}
    <div class="product-cover">
        <div class="product-image product-image-zoom" style=" position: relative;display: block;border: 1px solid #ededed;background: #fff;">
            <div style="z-index: 0;font-size: 26px;position: relative;">                
                <div style="display: block;position: absolute;top: 0%;left: -5%;-webkit-transform: scale3d(1.2, 1.2, 1.2);-moz-transform: scale3d(1.2, 1.2, 1.2);-o-transform: scale3d(1.2, 1.2, 1.2);-s-transform: scale3d(1.2, 1.2, 1.2);z-index: 9;">
                    <svg xmlns="http://www.w3.org/2000/svg" id="svgroot" xlinkns="http://www.w3.org/1999/xlink" width="500" height="500" x="0" y="0" overflow="visible">
                        <image  class ="predeco-image" height="{$products.cover.bySize.predeco.height}" width="{$products.cover.bySize.predeco.width}" 
                        y="{$products.cover.bySize.predeco.y}" x="{$products.cover.bySize.predeco.x}"  xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="{$products.cover.bySize.large_default.predecourl}" href="{$products.cover.bySize.large_default.predecourl}"></image>
                    </svg>
                </div>        
                <img class="js-qv-product-cover" src="{$products.cover.bySize.large_default.url}" alt="{$products.cover.legend}" title="{$products.cover.legend}" style="width:100%;" itemprop="image">
            </div>
        </div>
        <div class="layer hidden-sm-down" data-toggle="modal" data-target="#product-modal">
          <i class="material-icons zoom-in">&#xE8FF;</i>
        </div>
    </div>
    {/block}

    {block name='product_images'}
    <div class="js-qv-mask mask">
        <ul class="product-images js-qv-product-images">
            {foreach from=$products.images item=image}
                <li class="thumb-container" style="display: inline;position: relative; vertical-align: top;">
                <img
                  onclick="changePredecoImage({if !($products.is_default_attribute)}'{$image.bySize.home_default.predecourl}'{/if})"
                  id="js-thumbs"
                  class="thumb js-thumb {if $image.id_image == $products.cover.id_image} selected {/if}"
                  data-image-medium-src="{$image.bySize.medium_default.url}"
                  data-image-large-src="{$image.bySize.large_default.url}"
                  src="{$image.bySize.home_default.url}"
                  {if !($products.is_default_attribute)} data-predeco-url="{$image.bySize.home_default.predecourl}" {/if}
                  alt="{$image.legend}"
                  title="{$image.legend}"
                  width="100"
                  itemprop="image"
                  style="    height: 124px;
    width: 131px;"
                >
                {if !empty($image.bySize.home_default.predecourl)}
                <svg style="position: absolute;left: -48px; top: -42px;height: 2px!important;width: 2px!important;z-index:9999; overflow: visible;-webkit-transform: scale3d(0.4, 0.4, 0.4);-o-transform: scale3d(0.4, 0.4, 0.4);-s-transform: scale3d(0.4, 0.4, 0.4);
        z-index: 999;" xmlns="http://www.w3.org/2000/svg" id="svgroot" xlinkns="http://www.w3.org/1999/xlink" width="500" height="500" x="0" y="0" overflow="visible">
                    <image height="{$image.bySize.large_default.height}" width="{$image.bySize.large_default.width}" y="{$image.bySize.large_default.y}"
                     x="{$image.bySize.large_default.x}" id="layer_area_0" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="{$image.bySize.home_default.predecourl}" href="{$image.bySize.home_default.predecourl}"></image>
                </svg>
                {/if}
                </li>
            {/foreach}
        </ul>
    </div>
    {/block}
    {else}
        {block name='product_cover'}
        <div class="product-cover">
          {if $product.cover}
            <img class="js-qv-product-cover" src="{$product.cover.bySize.large_default.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" style="width:100%;" itemprop="image">
            <div class="layer hidden-sm-down" data-toggle="modal" data-target="#product-modal">
              <i class="material-icons zoom-in">&#xE8FF;</i>
            </div>
          {else}
            <img src="{$urls.no_picture_image.bySize.large_default.url}" style="width:100%;">
          {/if}
        </div>
      {/block}

      {block name='product_images'}
        <div class="js-qv-mask mask">
          <ul class="product-images js-qv-product-images">
            {foreach from=$product.images item=image}
              <li class="thumb-container">
                <img
                  class="thumb js-thumb {if $image.id_image == $product.cover.id_image} selected {/if}"
                  data-image-medium-src="{$image.bySize.medium_default.url}"
                  data-image-large-src="{$image.bySize.large_default.url}"
                  src="{$image.bySize.home_default.url}"
                  alt="{$image.legend}"
                  title="{$image.legend}"
                  width="100"
                  itemprop="image"
                >
              </li>
            {/foreach}
          </ul>
        </div>
      {/block}
    {/if}
</div>
{hook h='displayAfterProductThumbs'}
