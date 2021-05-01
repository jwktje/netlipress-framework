<?php

function wp_nav_menu($args)
{

    if (!empty($args) && !empty($args['menu_id']) && !empty($args['theme_location'])) {

        //Get nav items
        $menuFile = APP_ROOT . CONTENT_DIR . '/menu/' . $args['theme_location'] . '.json';
        if (file_exists($menuFile)) {
            $menuData = json_decode(file_get_contents($menuFile));
            $menuData->{'menu-items'} = $menuData->{'menu-items'} ?? [];

            $navMenu = '<ul id="' . $args["menu_id"] . '" class="menu">';
            foreach ($menuData->{'menu-items'} as $navItem) {
                $class = "menu-item";
                $class .= $navItem->class ? " " . $navItem->class : '';
                $navMenu .= '<li>';
                $navMenu .= '<a class="' . $class . '" href="' . $navItem->url . '">' . $navItem->title . '</a>';
                $navMenu .= '</li>';
            }
            $navMenu .= '</ul>';

            echo $navMenu;
        }
    }
}
