<?php
/**
 * Il Giornale delle PMI - tema custom.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;

define( 'GPMI_VERSION', '1.1.0' );
define( 'GPMI_DIR', get_template_directory() );
define( 'GPMI_URI', get_template_directory_uri() );

require GPMI_DIR . '/inc/setup.php';
require GPMI_DIR . '/inc/assets.php';
require GPMI_DIR . '/inc/performance.php';
require GPMI_DIR . '/inc/images.php';
require GPMI_DIR . '/inc/queries.php';
require GPMI_DIR . '/inc/template-tags.php';
require GPMI_DIR . '/inc/nav-walker.php';
require GPMI_DIR . '/inc/customizer.php';
require GPMI_DIR . '/inc/discovery.php';
require GPMI_DIR . '/inc/seo.php';
require GPMI_DIR . '/inc/preferred-source.php';
require GPMI_DIR . '/inc/comments.php';
require GPMI_DIR . '/inc/avatars.php';
