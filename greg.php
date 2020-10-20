<?php

/**
 * Plugin Name: Greg
 * Plugin URI: https://github.com/sitecrafting/greg
 * Author: SiteCrafting
 * Author URI: https://www.sitecrafting.com/
 * Description: A de-coupled calendar solution for WordPress and Timber
 * Version: 0.0.1
 * Requires PHP: 7.4
 */


// no script kiddiez
if (!defined('ABSPATH')) {
  return;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

use Timber\Timber;
use Twig\TwigFunction;
use Twig\Environment;

use Greg\Rest\RestController;
use Greg\WpCli\GregCommand;

define('GREG_PLUGIN_WEB_PATH', plugin_dir_url(__FILE__));
define('GREG_PLUGIN_JS_ROOT', GREG_PLUGIN_WEB_PATH . 'js');
define('GREG_PLUGIN_VIEW_PATH', __DIR__ . '/views');


add_action('init', function() {
  register_post_type('greg_event', [
    'public'                  => true,
    'description'             => 'Calendar Events, potentially with recurrences',
    'hierarchical'            => false,
    'rewrite'                 => [
      'slug'                  => 'event',
    ],
    'labels'                  => [
      'name'                  => 'Events',
      'singular_name'         => 'Event',
      'add_new_item'          => 'Schedule New Event',
      'edit_item'             => 'Edit Event',
      'new_item'              => 'New Event',
      'view_item'             => 'View Event',
      'view_items'            => 'View Events',
      'search_items'          => 'Search Events',
      'not_found'             => 'No Events found',
      'not_found_in_trash'    => 'No Events found in trash',
      'all_items'             => 'All Events',
      'archives'              => 'Event Archives',
      'attributes'            => 'Event Attributes',
      'insert_into_item'      => 'Insert into Event',
      'uploaded_to_this_item' => 'Uploaded to this Event',
    ],
    'menu_icon'               => 'dashicons-calendar',
    'has_archive'             => true, // TODO do we want this?
  ]);

  register_taxonomy('greg_event_category', ['greg_event'], [
    'public'                       => true,
    'rewrite'                      => [
      'slug'                       => 'event-category',
    ],
    'labels'                       => [
      'name'                       => 'Event Categories',
      'singular_name'              => 'Event',
      'menu_name'                  => 'Event Categories',
      'all_items'                  => 'All Event Categories',
      'edit_item'                  => 'Edit Event',
      'view_item'                  => 'View Event',
      'update_item'                => 'Update Event',
      'add_new_item'               => 'Add New Event',
      'new_item_name'              => 'New Event Name',
      'parent_item'                => 'Parent Event',
      'parent_item_colon'          => 'Parent Event:',
      'search_items'               => 'Search Event Categories',
      'popular_items'              => 'Popular Event Categories',
      'separate_items_with_commas' => 'Separate Event Categories with commas',
      'add_or_remove_items'        => 'Add or remove Event Categories',
      'choose_from_most_used'      => 'Choose from the most used Event Categories',
      'not_found'                  => 'No Event Categories found',
      'back_to_items'              => '← Back to Event Categories',
    ],
  ]);
});


/*
 * Add REST Routes
 */
add_action('rest_api_init', function() {
  $controller = new RestController();
  $controller->register_routes();
});


/*
 * Add WP-CLI tooling
 */
if (defined('WP_CLI') && WP_CLI) {
  $command = new GregCommand();
  WP_CLI::add_command('greg', $command);
}

add_action('wp_enqueue_scripts', function() {
  wp_enqueue_script(
    'greg',
    GREG_PLUGIN_WEB_PATH . 'js/greg.js',
    [], // deps
    false, // default to current WP version
    true // render in footer
  );
});

add_action('wp_enqueue_scripts', function() {
  wp_enqueue_script(
    'greg',
    GREG_PLUGIN_WEB_PATH . 'js/greg.js',
    [], // deps
    false, // default to current WP version
    true // render in footer
  );
});

/**
 * Example:
 *
 * apply_filters('greg/render', ['my_string' => 'Hello, World!']);
 */
add_filter('greg/render', function($tpl, $data = []) {
  // Allow for theme overrides
  $path = get_template_directory() . '/greg/' . $tpl;

  if (!file_exists($path)) {
    $path = GREG_PLUGIN_VIEW_PATH . $tpl;
  }

  if (file_exists($path)) {
    ob_start();
    require $path;
    return ob_get_clean();
  }
}, 10, 2);

/**
 * Merges in default data for the event-categories-list.twig view.
 */
add_filter('greg/render/event-categories-list.twig', function(array $data) : array {
  return array_merge($data, [
    'term'         => Timber::get_term(),
    'terms'        => Timber::get_terms([
      'taxonomy'   => 'greg_event_category',
      'hide_empty' => false,
    ]),
  ]);
});

add_filter('timber/locations', function(array $paths) {
  $paths['greg'] = [
    get_template_directory() . '/views/greg',
    GREG_PLUGIN_VIEW_PATH . '/twig',
  ];

  return $paths;
});

add_filter('timber/twig', function(Environment $twig) {
  $twig->addFunction(new TwigFunction('greg_compile', Greg\compile::class));
  $twig->addFunction(new TwigFunction('greg_render', Greg\render::class));
  $twig->addFunction(new TwigFunction('greg_get_events', Greg\get_events::class));

  return $twig;
});
