<?php
/**
 * Testata del sito.
 *
 * @package GPMI
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Vai al contenuto', 'gpmi' ); ?></a>

<div id="page" class="site">

	<header id="masthead" class="site-header">

		<?php if ( gpmi_option( 'topbar_date' ) || has_nav_menu( 'topbar' ) ) : ?>
			<div class="topbar">
				<div class="container topbar-inner">
					<?php if ( gpmi_option( 'topbar_date' ) ) : ?>
						<p class="topbar-date">
							<?php
							echo esc_html( wp_date( 'l, j F Y' ) );
							?>
							<span class="topbar-clock" data-clock hidden></span>
						</p>
					<?php endif; ?>

					<div class="topbar-actions">
						<?php gpmi_preferred_source_link(); ?>
					</div>

					<?php
					if ( has_nav_menu( 'topbar' ) ) {
						wp_nav_menu( array(
							'theme_location' => 'topbar',
							'menu_class'     => 'topbar-menu',
							'container'      => 'nav',
							'depth'          => 1,
							'fallback_cb'    => false,
						) );
					}
					?>
				</div>
			</div>
		<?php endif; ?>

		<div class="branding">
			<div class="container">
				<?php gpmi_site_branding(); ?>

				<?php
				$tagline = get_bloginfo( 'description', 'display' );
				if ( $tagline && gpmi_option( 'tagline_visible' ) ) :
					?>
					<p class="site-tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="navbar" data-sticky>
			<div class="container navbar-inner">

				<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu">
					<?php echo gpmi_icon( 'menu', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Apri il menu', 'gpmi' ); ?></span>
				</button>

				<a class="navbar-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-hidden="true" tabindex="-1">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				</a>

				<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Menu principale', 'gpmi' ); ?>">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'primary',
						'menu_id'        => 'primary-menu',
						'menu_class'     => 'primary-menu',
						'container'      => false,
						'walker'         => new GPMI_Nav_Walker(),
						'fallback_cb'    => false,
					) );
					?>
				</nav>

				<button class="search-toggle" type="button" aria-expanded="false" aria-controls="search-panel">
					<?php echo gpmi_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Cerca nel sito', 'gpmi' ); ?></span>
				</button>

			</div>

			<div id="search-panel" class="search-panel" hidden>
				<div class="container">
					<?php get_search_form(); ?>
				</div>
			</div>
		</div>

	</header>

	<?php get_template_part( 'template-parts/ticker' ); ?>

	<?php gpmi_breadcrumbs(); ?>

	<div id="content" class="site-content">
