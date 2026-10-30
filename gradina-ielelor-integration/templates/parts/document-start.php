<?php
/**
 * Începutul documentului comun tuturor paginilor Grădina Ielelor: head, body și sprite-ul de iconițe.
 * $args['shell_class'] — clasele containerului principal al paginii.
 */
if (!defined('ABSPATH')) {
    exit;
}
$gi_shell_class = isset($args['shell_class']) ? $args['shell_class'] : 'site-shell gi-site';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#173f35">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<svg class="gi-svg-sprite" aria-hidden="true" focusable="false">
    <symbol id="gi-icon-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
    <symbol id="gi-icon-arrow-up-right" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></symbol>
    <symbol id="gi-icon-calendar" viewBox="0 0 24 24"><path d="M6 2v4M18 2v4M3 9h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></symbol>
    <symbol id="gi-icon-mountain" viewBox="0 0 24 24"><path d="m3 20 7-13 4 7 2-3 5 9H3Z"/></symbol>
    <symbol id="gi-icon-music" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13M9 9l12-2"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></symbol>
    <symbol id="gi-icon-tent" viewBox="0 0 24 24"><path d="M3 20 12 3l9 17H3ZM12 3l3 17M9 20l3-8 3 8"/></symbol>
    <symbol id="gi-icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="gi-icon-waves" viewBox="0 0 24 24"><path d="M2 6c3-3 5 3 8 0s5 3 8 0 4 0 4 0M2 12c3-3 5 3 8 0s5 3 8 0 4 0 4 0M2 18c3-3 5 3 8 0s5 3 8 0 4 0 4 0"/></symbol>
    <symbol id="gi-icon-trees" viewBox="0 0 24 24"><path d="m8 3-5 8h3l-4 6h6v4h2v-4h5l-4-6h3L8 3ZM18 7l-3 5h2l-3 5h4v4h2v-4h3l-3-5h2l-4-5Z"/></symbol>
    <symbol id="gi-icon-map-pin" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
    <symbol id="gi-icon-phone" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></symbol>
    <symbol id="gi-icon-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></symbol>
    <symbol id="gi-icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="gi-icon-x" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></symbol>
    <symbol id="gi-icon-chevron-left" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
    <symbol id="gi-icon-chevron-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
    <symbol id="gi-icon-quote" viewBox="0 0 24 24"><path d="M10 11H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3a3 3 0 0 1 3 3v5a6 6 0 0 1-6 6M21 11h-5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3a3 3 0 0 1 3 3v5a6 6 0 0 1-6 6"/></symbol>
    <symbol id="gi-icon-droplet" viewBox="0 0 24 24"><path d="M12 2.7S5 10 5 14.5a7 7 0 0 0 14 0C19 10 12 2.7 12 2.7Z"/></symbol>
    <symbol id="gi-icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></symbol>
    <symbol id="gi-icon-flame" viewBox="0 0 24 24"><path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-9-1 2-2 3-3.5 3.5C11 6 10 4 9 2 7 6 5 9 5 15a7 7 0 0 0 7 7Z"/></symbol>
    <symbol id="gi-icon-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol>
</svg>

<div class="<?php echo esc_attr($gi_shell_class); ?>">
    <a class="skip-link" href="#continut">Sari la conținut</a>
