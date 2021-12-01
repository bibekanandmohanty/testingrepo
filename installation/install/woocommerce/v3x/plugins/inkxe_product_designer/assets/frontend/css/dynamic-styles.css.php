<?php global $jck_wt; ?>

<style>
    
    /* Default Styles */
    
    .jck-wt-all-images-wrap {
    	float: <?php echo $jck_wt['sliderPosition']; ?>;
    	width: <?php echo $jck_wt['sliderWidth']['width']; ?>;
    }
    
    /* Icon Styles */
    
    .jck-wt-icon-fullscreen,
    .jck-wt-icon-next,
    .jck-wt-icon-prev,
    .jck-wt-icon-loading {
        color: <?php echo $jck_wt['iconColour']; ?>;
    }
    
    /* Bullet Styles */
    
    .jck-wt-all-images-wrap .bx-pager-item a,
    .jck-wt-zoom-bullets .bx-pager-item a {
        border-color: <?php echo $jck_wt['iconColour']; ?>;
    }
    
    .jck-wt-all-images-wrap .bx-pager-item a.active,
    .jck-wt-zoom-bullets .bx-pager-item a.active {
        background-color: <?php echo $jck_wt['iconColour']; ?>;
    }
    
    /* Thumbnails */
    
    <?php if( $jck_wt['enableNavigation'] && $jck_wt['navigationType'] != "bullets" ) { ?>
    
        .jck-wt-all-images-wrap--thumbnails-left .jck-wt-thumbnails-wrap,
        .jck-wt-all-images-wrap--thumbnails-right .jck-wt-thumbnails-wrap {
            width: <?php echo $jck_wt['thumbnailWidth']; ?>%;
        }
        
        .jck-wt-all-images-wrap--thumbnails-left .jck-wt-images-wrap,
        .jck-wt-all-images-wrap--thumbnails-right .jck-wt-images-wrap {
            width: <?php echo 100-$jck_wt['thumbnailWidth']; ?>%;
        }
    
    <?php } else { ?>
    
        .jck-wt-all-images-wrap--thumbnails-left .jck-wt-images-wrap,
        .jck-wt-all-images-wrap--thumbnails-right .jck-wt-images-wrap {
            width: 100%;
        }
    
    <?php } ?>
    
    <?php $thumbnail_width = 100/(int)$jck_wt['thumbnailCount']; ?>
    
    /* Stacked Thumbnails - Left & Right */
    
    .jck-wt-all-images-wrap--thumbnails-left .jck-wt-thumbnails-wrap--stacked,
    .jck-wt-all-images-wrap--thumbnails-right .jck-wt-thumbnails-wrap--stacked {
        margin: 0;
    }
        
        .jck-wt-thumbnails-wrap--stacked .jck-wt-thumbnails__slide {
            width: <?php echo $thumbnail_width; ?>%;
        }
        
        /* Stacked Thumbnails - Left */
    
        .jck-wt-all-images-wrap--thumbnails-left .jck-wt-thumbnails-wrap--stacked .jck-wt-thumbnails__slide {
            padding: 0 <?php echo $jck_wt['thumbnailSpacing']; ?>px <?php echo $jck_wt['thumbnailSpacing']; ?>px 0;
        }
    
        /* Stacked Thumbnails - Right */
    
        .jck-wt-all-images-wrap--thumbnails-right .jck-wt-thumbnails-wrap--stacked .jck-wt-thumbnails__slide {
            padding: 0 0 <?php echo $jck_wt['thumbnailSpacing']; ?>px <?php echo $jck_wt['thumbnailSpacing']; ?>px;
        }
        
    /* Stacked Thumbnails - Above & Below */
    
    <?php 
    $thumbnail_gutter_left = floor($jck_wt['thumbnailSpacing']/2);
    $thumbnail_gutter_right = ceil($jck_wt['thumbnailSpacing']/2);
    ?>
    
    .jck-wt-all-images-wrap--thumbnails-above .jck-wt-thumbnails-wrap--stacked,
    .jck-wt-all-images-wrap--thumbnails-below .jck-wt-thumbnails-wrap--stacked {
        margin: 0 -<?php echo $thumbnail_gutter_left; ?>px 0 -<?php echo $thumbnail_gutter_right; ?>px;
    }
        
        /* Stacked Thumbnails - Above */
    
        .jck-wt-all-images-wrap--thumbnails-above .jck-wt-thumbnails-wrap--stacked .jck-wt-thumbnails__slide {
            padding: 0 <?php echo $thumbnail_gutter_left; ?>px <?php echo $jck_wt['thumbnailSpacing']; ?>px <?php echo $thumbnail_gutter_right; ?>px;
        }
        
        /* Stacked Thumbnails - Below */
    
        .jck-wt-all-images-wrap--thumbnails-below .jck-wt-thumbnails-wrap--stacked .jck-wt-thumbnails__slide {
            padding: <?php echo $jck_wt['thumbnailSpacing']; ?>px <?php echo $thumbnail_gutter_left; ?>px 0 <?php echo $thumbnail_gutter_right; ?>px;
        }
    
    /* Sliding Thumbnails - Left & Right, Above & Below */
    
    .jck-wt-all-images-wrap--thumbnails-left .jck-wt-thumbnails-wrap--sliding,
    .jck-wt-all-images-wrap--thumbnails-right .jck-wt-thumbnails-wrap--sliding {
        margin: 0;
    }
        
        .jck-wt-thumbnails-wrap--sliding .jck-wt-thumbnails__slide {
            width: <?php echo $thumbnail_width; ?>%;
        }
        
    /* Sliding Thumbnails - Left */

    .jck-wt-all-images-wrap--thumbnails-left .jck-wt-thumbnails-wrap--sliding {
        padding-right: <?php echo $jck_wt['thumbnailSpacing']; ?>px;
    }

    /* Sliding Thumbnails - Right */

    .jck-wt-all-images-wrap--thumbnails-right .jck-wt-thumbnails-wrap--sliding {
        padding-left: <?php echo $jck_wt['thumbnailSpacing']; ?>px;
    }
    
    /* Sliding Thumbnails - Above */

    .jck-wt-all-images-wrap--thumbnails-above .jck-wt-thumbnails-wrap--sliding {
        padding-bottom: <?php echo $jck_wt['thumbnailSpacing']; ?>px;
    }

    /* Sliding Thumbnails - Below */

    .jck-wt-all-images-wrap--thumbnails-below .jck-wt-thumbnails-wrap--sliding {
        padding-top: <?php echo $jck_wt['thumbnailSpacing']; ?>px;
    }
    	
    /* Zoom Styles */
    
    <?php if($jck_wt['zoomType'] == 'follow'):
    $borderRadius = ($jck_wt['zoomDimensions']['width'] > $jck_wt['zoomDimensions']['height']) ? $jck_wt['zoomDimensions']['width'] : $jck_wt['zoomDimensions']['height']; ?>
    .zm-viewer.shapecircular {
    	-webkit-border-radius: <?php echo $borderRadius; ?>px;
    	-moz-border-radius: <?php echo $borderRadius; ?>px;
    	border-radius: <?php echo $borderRadius; ?>px;
    }
    <?php endif; ?>
    
    .zm-handlerarea {
    	background: <?php echo $jck_wt['lensColour']; ?>;
    	-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=<?php echo $jck_wt['lensOpacity']*100; ?>)" !important;
    	filter: alpha(opacity=<?php echo $jck_wt['lensOpacity']*100; ?>) !important;
    	-moz-opacity: <?php echo $jck_wt['lensOpacity']; ?> !important;
    	-khtml-opacity: <?php echo $jck_wt['lensOpacity']; ?> !important;
    	opacity: <?php echo $jck_wt['lensOpacity']; ?> !important;
    }
    
    /* Media Queries */
    
    <?php if($jck_wt['enableBreakpoint']): ?>
    @media screen and (max-width: <?php echo $jck_wt['breakpoint']['width']; ?>) {
        
    	.jck-wt-all-images-wrap {
    		float: <?php echo $jck_wt['sliderPositionBreakpoint']; ?>;
    		width: <?php echo $jck_wt['sliderWidthBreakpoint']['width']; ?>;
    	}
    	
    	.jck-wt-all-images-wrap .bx-pager {
        	display: none;
    	}
    	
    }
    <?php endif; ?>
    
    /* Custom Styles */
    
    <?php echo $jck_wt['additionalCss']; ?>
    
</style>