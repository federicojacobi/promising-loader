<?php
/**
 * Promising Loader
 *
 * @package   promising-loader
 * @link      https://github.com/federicojacobi/promising-loader
 * @author    Federico Jacobi
 * @license   GPL v2 or later
 *
 * Plugin Name:  Promising Loader
 * Description:  A promise based JS loader to control execution order.
 * Version:      0.1
 * Author:       Federico Jacobi
 */

require_once( __DIR__ . '/class-pl-base.php' );
require_once( __DIR__ . '/class-pl-admin-ui.php' );

$promising_loader = new PL_Base();
new PL_Admin_UI();
