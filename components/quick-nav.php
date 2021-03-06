<?php
global $post;
$blocks = \CCL\Helpers\get_blocks(); 
$has_blocks = $blocks && count( $blocks ) > 1;
$page_options = (array) get_post_meta( $post->ID, 'page_options', true );
$is_spc = in_array( 'is_spc', $page_options ); // is special collections page
?>

<nav aria-label="Main Navigation" class="ccl-c-quick-nav <?php echo (! $has_blocks) ? 'ccl-no-secondary' : ''; ?>">

    <div class="ccl-c-quick-nav__bar">
            
        <div class="ccl-c-quick-nav__item ccl-is-brand">
            <a href="<?php echo get_home_url(); ?>" class="ccl-b-logo ccl-is-standalone">
                <span class="ccl-u-display-none"><?php echo bloginfo( 'name' ); ?></span>
            </a>
        </div>

        <div class="ccl-c-dropdown ccl-c-quick-nav__item ccl-is-primary">
        
            <a href="#dropdown-site-menus" class="ccl-c-dropdown__toggle ccl-c-quick-nav__item-text" data-toggle="dropdown" data-target="#dropdown-site-menus" aria-expanded="false" aria-haspopup="true">
                <?php 
                    if ( is_front_page() ) {
                        _e( 'Home', 'ccl' );
                    }
                    elseif ( is_search() ) {
                        _e( 'Search Results', 'ccl' );
                    }
                    elseif ( is_404() ) {
                        _e( 'Page not found', 'ccl' );
                    }
                    elseif ( is_archive() ) {
                        echo get_queried_object()->name;
                    } elseif ( is_singular( array( 'news' ) ) ) {
                        _e( 'News', 'ccl' );
                    } else {
                        the_title();
                    } 
                ?>
                <i class="ccl-b-caret-down" aria-hidden="true"></i>
            </a>
            
            <div id="dropdown-site-menus" class="ccl-c-dropdown__content">

                <?php if ( $is_spc && has_nav_menu( 'special_collections' ) ) {

                    wp_nav_menu( array(
                        'theme_location' => 'special_collections',
                        'container' => '',
                        'menu_class' => 'ccl-c-quick-nav__menu ccl-is-primary'
                    ) );

                } else {

                    wp_nav_menu( array(
                        'theme_location' => 'header_1',
                        'container' => '',
                        'menu_class' => 'ccl-c-quick-nav__menu ccl-is-primary'
                    ) );
    
                    wp_nav_menu( array(
                        'theme_location' => 'header_2',
                        'container' => '',
                        'menu_class' => 'ccl-c-quick-nav__menu ccl-is-secondary'
                    ) ); 

                } ?>

            </div>

        </div>

        <?php if ( $has_blocks ) : ?>

        <div class="ccl-c-dropdown ccl-c-quick-nav__item ccl-is-secondary">
            
            <a href="#dropdown-block-list" class="ccl-c-dropdown__toggle ccl-c-quick-nav__scrollspy ccl-c-quick-nav__item-text" data-toggle="dropdown" data-target="#dropdown-block-list" aria-expanded="false" aria-haspopup="true">
                <?php foreach ( $blocks as $index => $block ) :
                    if( 'search' == $block['block_type'] ){
                        $title = empty( $block['block_title'] ) ? 'Search' : $block['block_title'];
                    }else{
                        $title = empty( $block['block_title'] ) ? '(no title)' : $block['block_title'];                        
                    }
                ?>
                    <?php if ( $block['block_type'] != 'banner' ) : ?>
                        <span data-target="<?php echo '#block-' . $index; ?>">
                            <?php echo esc_html( $title ); ?>
                            <i class="ccl-b-caret-down" aria-hidden="true"></i>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </a>
            <div id="dropdown-block-list" class="ccl-c-dropdown__content">
                <ul class="ccl-c-quick-nav__menu ccl-is-primary">
                    <?php foreach ( $blocks as $index => $block ) : 
                        if( 'search' == $block['block_type'] ){
                            $title = empty( $block['block_title'] ) ? 'Search' : $block['block_title'];
                        }else{
                            $title = empty( $block['block_title'] ) ? '(no title)' : $block['block_title'];                        
                        }
                    ?>
                        <?php if ( $block['block_type'] != 'banner' ) : ?>
                            <li class="menu-item">
                                <a href="<?php echo '#block-' . $index; ?>" class="js-smooth-scroll">
                                    <?php echo esc_html( $title ); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
        </div>

        <?php endif; ?>

        <div class="ccl-c-quick-nav__item ccl-is-search-box">
            <?php \CCL\Helpers\get_component( 'search-box' ); ?>
        </div>

        <a href="#" class="ccl-c-quick-nav__item ccl-is-search-toggle">
            <span class="ccl-u-display-none">Search</span>
            <span class="ccl-b-icon search" aria-hidden="true"></span>
        </a>
    
    </div>

</nav>