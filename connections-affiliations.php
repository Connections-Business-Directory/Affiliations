<?php
/**
 * An extension for the Connections Business Directory which add a repeatable field for entering entry affiliations.
 *
 * @package   Connections Business Directory Affiliations
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2017 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Affiliations
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for the Connections Business Directory which add a repeatable field for entering entry affiliations.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections-affiliations
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Connections_Affiliations' ) ) {

	final class Connections_Affiliations {

		const VERSION = '1.0';

		/**
		 * @var string The absolute path this this file.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $basename = '';

		public function __construct() {

			self::$file       = __FILE__;
			self::$url        = plugin_dir_url( self::$file );
			self::$path       = plugin_dir_path( self::$file );
			self::$basename   = plugin_basename( self::$file );

			self::loadDependencies();

			// This should run on the `plugins_loaded` action hook. Since the extension loads on the
			// `plugins_loaded action hook, call immediately.
			self::loadTextdomain();

			// Register CSS and JavaScript.
			add_action( 'init', array( __CLASS__ , 'registerScripts' ) );

			// Add to Connections menu.
			add_filter( 'cn_submenu', array( __CLASS__, 'addMenu' ) );

			// Add bulk action to Categories to convert to Affiliation.
			add_filter( 'bulk_actions-connections_page_connections_categories', array( __CLASS__, 'registerBulkActions' ) );

			// Callbacks to process bulk actions.
			add_action( 'bulk_term_action-category-convert_to_affiliate', array( __CLASS__, 'processConvertCategoryToAffiliate' ) );

			// Remove the "View" link from the "Facility" taxonomy admin page.
			add_filter( 'cn_affiliate_row_actions', array( __CLASS__, 'removeViewAction' ) );

			// Register the metabox.
			add_action( 'cn_metabox', array( __CLASS__, 'registerMetabox') );

			// Law License uses a custom field type, so let's add the action to add it.
			add_action( 'cn_meta_field-affiliations', array( __CLASS__, 'field' ), 10, 3 );

			// Since we're using a custom field, we need to add our own sanitization method.
			add_filter( 'cn_meta_sanitize_field-affiliations', array( __CLASS__, 'sanitize' ) );

			// Attach Affiliations to entry when saving an entry.
			add_action( 'cn_process_taxonomy-category', array( __CLASS__, 'attachAffiliations' ), 9, 2 );
			//add_filter( 'cn_pre_save_meta', array( __CLASS__, 'attachAffiliations' ), 10, 3 );

			// Add the "Facilities" option to the admin settings page.
			// This is also required so it'll be rendered by $entry->getContentBlock( 'affiliations' ).
			add_filter( 'cn_content_blocks', array( __CLASS__, 'registerContentBlockOptions') );

			// Add the action that'll be run when calling $entry->getContentBlock( 'affiliations' ) from within a template.
			add_action( 'cn_output_meta_field-affiliations', array( __CLASS__, 'block' ), 10, 4 );

			// Register the widget.
			//add_action( 'widgets_init', array( 'CN_Affiliations_Widget', 'register' ) );
		}

		/**
		 * The widget.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @return void
		 */
		private static function loadDependencies() {

			//require_once( self::$path . 'includes/class.widgets.php' );
		}

		/**
		 * Load the plugin translation.
		 *
		 * Credit: Adapted from Ninja Forms / Easy Digital Downloads.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @return void
		 */
		public static function loadTextdomain() {

			// Plugin textdomain. This should match the one set in the plugin header.
			$domain = 'connections-affiliations';

			// Set filter for plugin's languages directory
			$languagesDirectory = apply_filters( "cn_{$domain}_languages_directory", dirname( self::$file ) . '/languages/' );

			// Traditional WordPress plugin locale filter
			$locale   = apply_filters( 'plugin_locale', get_locale(), $domain );
			$fileName = sprintf( '%1$s-%2$s.mo', $domain, $locale );

			// Setup paths to current locale file
			$local  = $languagesDirectory . $fileName;
			$global = WP_LANG_DIR . "/{$domain}/" . $fileName;

			if ( file_exists( $global ) ) {

				// Look in global `../wp-content/languages/{$domain}/` folder.
				load_textdomain( $domain, $global );

			} elseif ( file_exists( $local ) ) {

				// Look in local `../wp-content/plugins/{plugin-directory}/languages/` folder.
				load_textdomain( $domain, $local );

			} else {

				// Load the default language files
				load_plugin_textdomain( $domain, FALSE, $languagesDirectory );
			}
		}

		public static function registerScripts() {

			// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
			$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$min = '';

			$requiredCSS = class_exists( 'Connections_Form' ) ? array( 'cn-public', 'cn-form-public' ) : array( 'cn-public' );

			// Register CSS.
			//wp_register_style( 'cnbh-admin' , CNBH_URL . "assets/css/cnbh-admin$min.css", array( 'cn-admin', 'cn-admin-jquery-ui' ) , CNBH_CURRENT_VERSION );
			//wp_register_style( 'cnbh-public', CNBH_URL . "assets/css/cnbh-public$min.css", $requiredCSS, CNBH_CURRENT_VERSION );

			// Register JavaScript.
			//wp_register_script( 'jquery-timepicker' , CNBH_URL . "assets/js/jquery-ui-timepicker-addon$min.js", array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ) , '1.4.3' );
			wp_register_script( 'cna-ui-js' , self::$url . "assets/js/cna-common$min.js", array( 'jquery-chosen', 'jquery-ui-sortable' ) , self::VERSION, true );

			//wp_localize_script( 'cnbh-ui-js', 'cnbhDateTimePickerOptions', Connections_Business_Hours::dateTimePickerOptions() );
		}

		public static function addMenu( $menu ) {

			$menu['61.97']  = array(
				'hook'       => 'affiliations',
				'page_title' => 'Connections : ' . __( 'Affiliations', 'connections-affiliations' ),
				'menu_title' => __( 'Affiliations', 'connections-affiliations' ),
				'capability' => 'connections_edit_categories',
				'menu_slug'  => 'connections_affiliations',
				'function'   => array( __CLASS__, 'showPage' ),
			);

			return $menu;
		}

		public static function showPage() {

			// Grab an instance of the Connections object.
			$instance = Connections_Directory();

			if ( $instance->dbUpgrade ) {

				include_once CN_PATH . 'includes/inc.upgrade.php';
				connectionsShowUpgradePage();
				return;
			}

			switch ( $_GET['page'] ) {

				case 'connections_affiliations':
					include_once self::$path . 'includes/admin/pages/affiliations.php';
					connectionsShowAffiliatePage();
					break;
			}
		}

		/**
		 * Callback for the `bulk_actions-connections_page_connections_categories` filter.
		 *
		 * @param array $actions
		 *
		 * @return array
		 */
		public static function registerBulkActions( $actions ) {

			$actions['convert_to_affiliate'] = 'Convert to Affiliate';

			return $actions;
		}

		/**
		 * Callback for the `bulk_term_action-category-convert_to_affiliate` action.
		 */
		public static function processConvertCategoryToAffiliate() {

			self::convertTaxonomy( $_REQUEST['category'], 'category', 'affiliate' );
		}

		/**
		 * Convert an array of term ID/s from one taxonomy to another.
		 *
		 * NOTE: When converting a parent term, its descendants will also be converted but the hierarchy is not preserved.
		 *
		 * @param array  $term_ids An Array of term ID to convert.
		 * @param string $from     The taxonomy to convert from.
		 * @param string $to       The taxonomy to convert to.
		 *
		 * @return bool
		 */
		private static function convertTaxonomy( $term_ids, $from, $to ) {

			global $wpdb;

			//$to = $_POST['new_tax'];
			//
			//if ( ! taxonomy_exists( $to ) ) {
			//	return FALSE;
			//}
			//
			//if ( $to == $from ) {
			//	return FALSE;
			//}

			$tt_ids = array();
			$table  = CN_TERM_TAXONOMY_TABLE;

			foreach ( $term_ids as $term_id ) {

				$term = cnTerm::get( $term_id, $from );

				if ( $term->parent && ! in_array( $term->parent, $term_ids ) ) {

					$wpdb->update(
						$table,
						array( 'parent' => 0 ),
						array( 'term_taxonomy_id' => $term->term_taxonomy_id )
					);
				}

				$tt_ids[] = $term->term_taxonomy_id;

				//if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				if ( TRUE ) {

					$child_terms = cnTerm::getTaxonomyTerms(
						$from,
						array(
							'child_of'   => $term_id,
							'hide_empty' => FALSE,
						)
					);

					$tt_ids = array_merge( $tt_ids, wp_list_pluck( $child_terms, 'term_taxonomy_id' ) );
				}
			}

			$tt_ids = implode( ',', array_map( 'absint', $tt_ids ) );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table SET taxonomy = %s WHERE term_taxonomy_id IN ( $tt_ids )",
					$to
				)
			);

			//if ( is_taxonomy_hierarchical( $from ) && ! is_taxonomy_hierarchical( $to ) ) {
			if ( TRUE ) {

				$wpdb->query( "UPDATE $table SET parent = 0 WHERE term_taxonomy_id IN ( $tt_ids )" );
			}

			cnTerm::cleanCache( $tt_ids, $from );
			cnTerm::cleanCache( $tt_ids, $to );

			//do_action( 'term_management_tools_term_changed_taxonomy', $tt_ids, $to, $from );

			return TRUE;
		}

		public static function removeViewAction( $actions ) {

			unset( $actions['view'] );

			return $actions;
		}

		/**
		 * Registered the custom metabox.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 */
		public static function registerMetabox() {

			$atts = array(
				'id'       => 'metabox-affiliations',
				'title'    => __( 'Affiliations', 'connections-affiliations' ),
				'context'  => 'normal',
				'priority' => 'core',
				'fields'   => array(
					array(
						'id'    => 'affiliations',
						'type'  => 'affiliations',
					),
				),
			);

			cnMetaboxAPI::add( $atts );
		}

		/**
		 * Callback for the `cn_content_blocks` filter.
		 *
		 * Add the custom meta as an option in the content block settings in the admin.
		 * This is required for the output to be rendered by $entry->getContentBlock().
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param array $blocks An associative array containing the registered content block settings options.
		 *
		 * @return array
		 */
		public static function registerContentBlockOptions( $blocks ) {

			$blocks['affiliations'] = __( 'Affiliations', 'connections-affiliations' );

			return $blocks;
		}

		/**
		 * @param array   $field
		 * @param array   $value
		 * @param cnEntry $entry
		 */
		public static function field( $field, $value, $entry ) {

			// Setup a default value if no licenses exist so the first license row is rendered.
			if ( empty( $value ) ) {

				$value = array(
					array(
						'affiliate' => '',
					)
				);
			}

			?>
			<style type="text/css" scoped>
				#cn-affiliations thead td {
					vertical-align: bottom;
				}
				i.fa.fa-sort {
					cursor: move;
					padding-bottom: 4px;
					padding-right: 4px;
					vertical-align: middle;
				}
				i.cnd-clearable__clear {
					display: none;
					position: absolute;
					right: 0;
					top: 0;
					font-style: normal;
					user-select: none;
					cursor: pointer;
					font-size: 1.5em;
					padding: 0 8px;
				}
				@media screen and ( max-width: 782px ) {
					i.cnd-clearable__clear {
						font-size: 2.15em;
						/*padding: 7px 8px;*/
					}
				}
			</style>
			<table id="cn-affiliations" data-count="<?php echo count( $value ) ?>">

				<thead>
				<tr>
					<td>&nbsp;</td>
					<td><?php _e( 'Affiliations', 'connections-affiliations' ); ?></td>
					<td><?php _e( 'Add / Remove', 'connections-affiliations' ); ?></td>
				</tr>
				</thead>

				<tbody>

				<?php foreach ( $value as $affiliate ) : ?>

					<tr class="widget">
						<td><i class="fa fa-sort"></i></td>
						<td style="width: 80%">
							<?php

							cnTemplatePart::walker(
								'term-select-enhanced',
								array(
									'taxonomy'        => 'affiliate',
									'name'            => $field['id'] . '[0][affiliate]',
									'class'           => array('cn-affiliate-select'),
									'style'           => array( 'min-width' => '150px', 'width' => '100%' ),
									'show_option_all' => '',
									'default'         => __( 'Select Affiliate', 'connections-affiliations' ),
									'selected'        => cnArray::get( $affiliate, 'affiliate', 0 ),
								)
							);

							?>
						</td>
						<td>
							<span class="button disabled cna-remove-affiliate">&ndash;</span><span class="button cna-add-affiliate">+</span>
						</td>
					</tr>

				<?php endforeach; ?>

				</tbody>
			</table>

			<?php

			// Enqueue the JS required for the metabox.
			wp_enqueue_script( 'cna-ui-js' );
		}

		/**
		 * Sanitize the times as a text input using the cnSanitize class.
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param array $value
		 *
		 * @return array
		 */
		public static function sanitize( $value ) {

			if ( empty( $value ) ) return $value;

			foreach ( $value as $key => &$affiliate ) {

				if ( 0 != $affiliate['affiliate'] || 0 != $affiliate['affiliate'] ) {

					$affiliate['affiliate'] = absint( $affiliate['affiliate'] );

				} else {

					unset( $value[ $key ] );
				}
			}

			return $value;
		}

		/**
		 * Add, update or delete the entry affiliations.
		 *
		 * @access public
		 * @since  1.0
		 * @static
		 *
		 * @param  string $action The action to being performed to an entry.
		 * @param  int    $id     The entry ID.
		 */
		public static function attachAffiliations( $action, $id ) {

			// Grab an instance of the Connections object.
			$instance   = Connections_Directory();
			$affiliates = array();

			if ( isset( $_POST['affiliations'] ) && ! empty( $_POST['affiliations'] ) ) {

				foreach ( $_POST['affiliations'] as $key => &$affiliate ) {

					if ( 0 != $affiliate['affiliate'] || 0 != $affiliate['affiliate'] ) {

						$affiliates[] = absint( $affiliate['affiliate'] );
					}
				}

			}

			$instance->term->setTermRelationships( $id, $affiliates, 'affiliate' );
		}

		/**
		 * The output of the license data.
		 *
		 * Called by the cn_meta_output_field-affiliations action in cnOutput->getMetaBlock().
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param string  $id    The field id.
		 * @param array   $value The license data.
		 * @param cnEntry $object
		 * @param array   $atts  The shortcode atts array passed from the calling action.
		 */
		public static function block( $id, $value, $object = NULL, $atts ) {
			?>

			<div class="cn-affiliations">
				<ul class="cn-affiliation">

				<?php

				foreach ( $value as $key => &$item ) {

					$affiliate  = $item['affiliate'] ? cnTerm::get( $item['affiliate'], 'affiliate' ) : 0;

					if ( $affiliate ) : ?>
						<li class="cn-license cn-affiliate"><span class="cn-value"><?php echo esc_html( $affiliate->name ); ?></span></li>
					<?php endif;
				}

				?>

				</ul>
			</div>

			<?php
		}
	}

	/**
	 * Start up the extension.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return mixed object | bool
	 */
	function Connections_Affiliations() {

			if ( class_exists('connectionsLoad') ) {

					return new Connections_Affiliations();

			} else {

				add_action(
					'admin_notices',
					 function() {
						echo '<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Affiliations.</p></div>';
						}
				);

				return FALSE;
			}
	}

	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11 so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Affiliations', 11 );

}
