<?php

function wp_nav_menu($args)
{

    if (!empty($args) && !empty($args['menu_id']) && !empty($args['theme_location'])) {

        //Get nav items
        $menuFile = APP_ROOT . CONTENT_DIR . '/menu/' . $args['theme_location'] . '.json';
        if (file_exists($menuFile)) {
            $menuData = json_decode(file_get_contents($menuFile));
            $menuData->{'menu-items'} = $menuData->{'menu-items'} ?? [];

            $navMenu = '<ul id="' . $args["menu_id"] . '" class="' . ($args['menu_class'] ?? 'menu') . '">';
            foreach ($menuData->{'menu-items'} as $navItem) {
                $classes = ['menu-item'];
                if (isset($navItem->class)) {
                    $classes[] .= $navItem->class;
                }
                if ($navItem->url === get_permalink()) {
                    $classes[] .= 'current_page_item';
                }
                $navMenu .= '<li class="' . implode(' ', $classes) . '">';
                $navMenu .= '<a href="' . $navItem->url . '">' . $navItem->title . '</a>';
                $navMenu .= '</li>';
            }
            $navMenu .= '</ul>';

            echo $navMenu;
        }
    }
}

function wp_get_nav_menu_items($location)
{
    $menuFile = APP_ROOT . CONTENT_DIR . '/menu/' . $location . '.json';
    if (file_exists($menuFile)) {
        $menuData = json_decode(file_get_contents($menuFile));
        return $menuData->{'menu-items'} ?? [];
    }
}
