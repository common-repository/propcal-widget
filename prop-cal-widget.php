<?php
/**
 * Copyright (c) 2017.  - Prop-Sync - All Rights Reserved
 */

/*
Plugin Name: PropCal Widget Plugin
Plugin URI: http://prop-sync.com/
Description: Provides a customizable calendar for Prop-Sync Properties
Version: 0.3.19
Author: BraceIT
Author URI: http://braceit.net/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Security Measure
defined( 'ABSPATH' ) or die( 'File should not be directly accessed' );

// Mustache is used as the template engine throughout this plugin.
require plugin_dir_path(__FILE__) . "content/mustache/src/Mustache/Autoloader.php";
Mustache_Autoloader::register();

// Generates the displayed calendar for a shortcode.
function pcw_cal_gen_sc( $atts ) {
    global $wpdb;
    $pcw_cals_t = $wpdb->prefix . "pcw_cals";
    $sc_atts = shortcode_atts(array(
        'id' => null,
    ), $atts);
    $cal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $pcw_cals_t WHERE id = %d", $sc_atts['id']), ARRAY_A);

    wp_enqueue_style("pcw_default_cal_style");

    $mustache = new Mustache_Engine;

    $output = "";

    $dat = array();

    if ($sc_atts['id'] != null) {
        $output .= $mustache->render(
            file_get_contents( plugin_dir_path(__FILE__) . "content/pcwidget.html.mustache"),
            $cal
        );
        $output .= "<script>";
        if ($cal['stub_uat']) {
            $dat = array_merge($cal, array(
                'api_url' => "https://poll.uat.prop-sync.co.uk:10080/PropCal/rest/polling/getCalendar"
            ));
        } else {
            $dat = array_merge($cal, array(
                'api_url' => "https://poll.prop-sync.co.uk/PropCal/rest/polling/getCalendar"
            ));
        }
        $output .= $mustache->render(
            file_get_contents( plugin_dir_path(__FILE__) . "content/pcwidget.js.mustache"),
            $dat
        );
        $output .= "</script>";
    } else {
        $output .= "Widget ID Required";
    }

    return $output;
}

// Generates the admin menu
function pcw_admin_menu( $atts ) {
    global $wpdb;
    $pcw_cals_t = $wpdb->prefix . "pcw_cals";
    $cals = $wpdb->get_results("SELECT * FROM $pcw_cals_t ORDER BY id ASC;", ARRAY_A);

    $mustache = new Mustache_Engine;
    echo $mustache->render(
        file_get_contents( plugin_dir_path(__FILE__) . "content/ADMIN.html.mustache"),
        array(
            'admin_url' => admin_url(),
            'widgets' => $cals
        )
    );
}

// Generates the add new menu
function pcw_add_widget_menu( $atts ) {
    $mustache = new Mustache_Engine;

    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        echo $mustache->render(
            file_get_contents( plugin_dir_path(__FILE__) . "content/ADD.html.mustache"),
            array(
                'id' => null,
                'admin_url' => admin_url(),
                'mod' => "Add",
                'name' => "",
                'unit_id' => "",
                'show_numbers' => true,
                'show_colours' => true,
                'note' => "",
                'stub_uat' => false
            )
        );
    } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
        $name = $_POST['pcw_name'];
        $unit_id = $_POST['pcw_pid'];
        $note = $_POST['pcw_note'];
        $show_numbers = isset($_POST['pcw_number']);
        $show_colours = isset($_POST['pcw_colour']);
        $stub_uat = isset($_POST['stub_uat']);

        global $wpdb;
        $pcw_cals_t = $wpdb->prefix . "pcw_cals";

        $wpdb->insert(
            $pcw_cals_t,
            array(
                "name" => $name,
                "show_colours" => $show_colours,
                "show_numbers" => $show_numbers,
                "unit_id" => $unit_id,
                "note" => $note,
                "stub_uat" => $stub_uat
            ),
            array(
                "%s",
                "%d",
                "%d",
                "%d",
                "%s",
                "%d"
            )
        );
        echo "<div class='wrap'>Redirecting, if redirect fails click <a href='".admin_url()."admin.php?page=pcw_admin_menu'>here</a></div>";
        echo "<script>window.location.replace('".admin_url()."admin.php?page=pcw_admin_menu');</script>";
    }

}

// Generates the edit menu
function pcw_edit_widget_menu( $atts ) {
    $mustache = new Mustache_Engine;
    global $wpdb;
    $pcw_cals_t = $wpdb->prefix . "pcw_cals";

    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        $widget_id = $_GET['wid'];
        $cal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $pcw_cals_t WHERE id = %d", $widget_id), ARRAY_A);

        echo $mustache->render(
            file_get_contents( plugin_dir_path(__FILE__) . "content/ADD.html.mustache"),
            array_merge($cal, array(
                'admin_url' => admin_url(),
                'mod' => "Edit",
            ))
        );
    } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
        $id = $_POST['wid'];
        $name = stripslashes($_POST['pcw_name']);
        $unit_id = stripslashes($_POST['pcw_pid']);
        $note = stripslashes($_POST['pcw_note']);
        $show_numbers = isset($_POST['pcw_number']);
        $show_colours = isset($_POST['pcw_colour']);
        $stub_uat = isset($_POST['stub_uat']);

        $wpdb->update(
            $pcw_cals_t, // Table
            array( // Data
                "name" => $name,
                "show_colours" => $show_colours,
                "show_numbers" => $show_numbers,
                "unit_id" => $unit_id,
                "note" => $note,
                "stub_uat" => $stub_uat
            ),
            array( // Where
                "id" => $id
            ),
            array( // Data Format
                "%s",
                "%d",
                "%d",
                "%s",
                "%s",
                "%d"
            ),
            array( // Where Format
                "%d"
            )
        );
        echo "<div class='wrap'>Redirecting, if redirect fails click <a href='".admin_url()."admin.php?page=pcw_admin_menu'>here</a></div>";
        echo "<script>window.location.replace('".admin_url()."admin.php?page=pcw_admin_menu');</script>";
    }
}

// Generates the delete menu
function pcw_remove_widget_menu( $atts ) {
    $mustache = new Mustache_Engine;
    global $wpdb;
    $pcw_cals_t = $wpdb->prefix . "pcw_cals";

    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        $widget_id = $_GET['wid'];
        $cal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $pcw_cals_t WHERE id = %d", $widget_id), ARRAY_A);

        echo $mustache->render(
            file_get_contents( plugin_dir_path(__FILE__) . "content/REMOVE.html.mustache"),
            $cal
        );

    } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
        $widget_id = $_POST['wid'];
        $delete = ($_POST['action']=="Delete, Permanently Remove Widget");

        if ($delete) {
            $wpdb->delete(
                $pcw_cals_t, // Table
                array( // Where
                    "id" => $widget_id
                ),
                array( // Where Format
                    "%d"
                )
            );
        }
        echo "<div class='wrap'>Redirecting, if redirect fails click <a href='".admin_url()."admin.php?page=pcw_admin_menu'>here</a></div>";
        echo "<script>window.location.replace('".admin_url()."admin.php?page=pcw_admin_menu');</script>";
    }
}

// Installs the plugin, setting up database.
function install_pcw_propcal_widget() {
    global $wpdb;
    $cals_table_name = $wpdb->prefix . "pcw_cals";
    $charset_collate = $wpdb->get_charset_collate();

    $cals_mysql = "CREATE TABLE $cals_table_name (
        id int(10) NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        show_colours bool NOT NULL,
        show_numbers bool NOT NULL,
        unit_id text NOT NULL,
        stub_uat bool NOT NULL,
        note text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
    dbDelta($cals_mysql);
}

// Adds all menu hooks
function register_pcw_menu() {
    add_menu_page(
        "PropCal Widgets", // Page Title
        "PropCal Widgets", // Menu Title
        "edit_pages", // Capability
        "pcw_admin_menu", // Menu Slug
        "pcw_admin_menu", // Function
        "dashicons-hammer" // Icon
    );
    add_submenu_page(
        "pcw_admin_menu", // Parent Slug
        "Add Widget", // Page Title
        "Add Widget", // Menu Title
        "edit_pages", // Capability
        "pcw_add_widget_menu", // Menu Slug
        "pcw_add_widget_menu" // Function
    );
    add_submenu_page(
        NULL, // Parent Slug
        "Edit Widget", // Page Title
        "Edit Widget", // Menu Title
        "edit_pages", // Capability
        "pcw_edit_widget_menu", // Menu Slug
        "pcw_edit_widget_menu" // Function
    );
    add_submenu_page(
        NULL, // Parent Slug
        "Remove Widget", // Page Title
        "Remove Widget", // Menu Title
        "edit_pages", // Capability
        "pcw_remove_widget_menu", // Menu Slug
        "pcw_remove_widget_menu" // Function
    );
}

// Adds all script hooks
function register_pcw_scripts() {
    wp_register_style('pcw_default_cal_style', plugins_url("content/pcwidget.css", __FILE__));
}

// Register plugin hooks, actions, and shortcodes
register_activation_hook(__FILE__, "install_pcw_propcal_widget");
add_action("admin_menu", "register_pcw_menu");
add_action("wp_enqueue_scripts", "register_pcw_scripts");
add_shortcode("pcw_cals", "pcw_cal_gen_sc");

?>
