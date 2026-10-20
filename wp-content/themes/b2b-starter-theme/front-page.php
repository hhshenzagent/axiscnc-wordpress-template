<?php
/**
 * Static front page dispatcher controlled by Homepage layout ACF field.
 *
 * @package b2b-starter
 */

if (get_page_template_slug(get_queried_object_id()) === 'page-templates/axis-home.php') {
    AxisCNC\SuiteTheme\render_home();
} else {
    Starter\Theme\render_home_layout(Starter\Theme\home_template_value());
}

