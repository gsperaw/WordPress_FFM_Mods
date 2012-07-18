<?php

/*

 Plugin Name: Front File Manager

 Plugin URI: http://www.webikon.sk/

 Description: Allow your visitors to upload files from front-end. You can list these files on any page using shortcode. 

 Version: 0.1

 Author: Ján Bočínec

 Author URI: http://johnnypea.wp.sk/

*/



/*  Copyright 2012 Ján Bočínec (email : jan.bocinec@webikon.sk)



 This program is free software; you can redistribute it and/or modify

 it under the terms of the GNU General Public License as published by

 the Free Software Foundation; either version 2 of the License, or

 (at your option) any later version.



 This program is distributed in the hope that it will be useful,

 but WITHOUT ANY WARRANTY; without even the implied warranty of

 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

 GNU General Public License for more details.



 You should have received a copy of the GNU General Public License

 along with this program; if not, write to the Free Software

 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */



/* 

Thanks to Jonathan Christopher (email : jonathan@irontoiron.com) for the code below from Front End Upload plugin http://wordpress.org/extend/plugins/front-end-upload/  

*/



include 'includes.php';



// constant definition

if( !defined( 'IS_ADMIN' ) )

    define( 'IS_ADMIN', is_admin() );



define( 'FFM_VERSION', '0.4' );

define( 'FFM_PREFIX', '_iti_ffm_' );

define( 'FFM_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );

define( 'FFM_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

$uploads_dir = wp_upload_dir();

define( 'FFM_DESTINATION_DIR', $uploads_dir['path'].'/' );

define( 'FFM_DESTINATION_URL', $uploads_dir['url'].'/' );



// WordPress actions

if( IS_ADMIN )

{

    add_action( 'admin_init',           array( 'FrontFileManager', 'environment_check' ) );

    add_action( 'admin_init',           array( 'FrontFileManager', 'register_settings' ) );

    add_action( 'admin_menu',           array( 'FrontFileManager', 'assets' ) );



    add_filter( 'plugin_row_meta',      array( 'FrontFileManager', 'filter_plugin_row_meta' ), 10, 2 );

}

else

{

    // we depend on jQuery and Plupload on the front end

    add_action( 'init',                 array( 'FrontFileManager', 'assets_public' ) );

    add_action( 'get_footer',           array( 'FrontFileManager', 'init_plupload' ) );

}





/**

 * Front File Manager

 *

 * @package WordPress

 * @author Jonathan Christopher

 **/

class FrontFileManager

{

    public $settings    = array(

            'version'   => FFM_VERSION,

            );





    /**

     * Constructor

     * Sets default options, initializes localization and shortcodes

     *

     * @return void

     * @author Jonathan Christopher

     */

    function __construct()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        if( !$settings )

        {

            // first run

            self::first_run();

            add_option( FFM_PREFIX . 'settings', $this->settings, '', 'yes' );

        }

        else

        {

            $this->settings = $settings;

        }



        // localization

        self::l10n();



        // shortcode init

        if( !IS_ADMIN )

        {

            self::init_shortcodes();

        }

    }





    function mkdir_recursive( $path )

    {

        if ( empty( $path ) ) { // prevent infinite loop on bad path

            return;

        }

        is_dir( dirname( $path ) ) || $this->mkdir_recursive( dirname( $path ) );

        if ( is_dir( $path ) === true )

        {

            return true;

        } else

        {

            return @mkdir( $path );

        }

    }





    /**

     * Checks to ensure we have proper WordPress and PHP versions

     *

     * @return void

     * @author Jonathan Christopher

     */

    function environment_check()

    {

        $wp_version = get_bloginfo( 'version' );

        if( !version_compare( PHP_VERSION, '5.2', '>=' ) || !version_compare( $wp_version, '3.2', '>=' ) )

        {

            if( IS_ADMIN && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )

            {

                require_once ABSPATH.'/wp-admin/includes/plugin.php';

                deactivate_plugins( __FILE__ );

                wp_die( __('Front File Manager requires WordPress 3.2 or higher, it has been automatically deactivated.') );

            }

            else

            {

                return;

            }

        }

        else

        {

            // PHP and WP versions check out, let's try to set up our upload destination

            if( !file_exists( FFM_DESTINATION_DIR ) )

            {

                if ( self::mkdir_recursive( FFM_DESTINATION_DIR ) === false )

                {

                    wp_die( __('Error: Unable to create upload storage directory. Please verify write permissions to the designated WordPress uploads directory. Front File Manager has been deactivated.') );

                }

            }

        }

    }





    /**

     * Runs on first activation of plugin

     *

     * @return void

     * @author Jonathan Christopher

     */

    function first_run()

    {

        // null

    }





    /**

     * Load the translation of the plugin

     *

     * @return void

     * @author Jonathan Christopher

     */

    function l10n()

    {

        load_plugin_textdomain( 'frontfilemanager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    }





    /**

     * Initialize appropriate shortcod

     *

     * @return void

     * @author Jonathan Christopher

     */

    function init_shortcodes()

    {

        add_shortcode( 'ffm-uploader', array( 'FrontFileManager', 'shortcode' ) );

    }





    /**

     * Shortcode handler that outputs the form, Plupload, and any feedback messages

     *

     * @return string $output Formatted HTML to be used in the theme

     * @author Jonathan Christopher

     */

    function shortcode( $atts )

    {

        // grab FFM's settings

        $settings   = get_option( FFM_PREFIX . 'settings' );



        if ( $settings['only_registered'] && !is_user_logged_in() ) return __( 'You have to <a href="'.wp_login_url( get_permalink() ).'"><b>log in</b></a> to upload the files!', 'frontfilemanager' );        



        $output     =  '<div class="front-file-manager-parent">';



        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';



        // do we need to show the form?

        if( ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'feuaccess' ) ) )

        {

            // verified upload submission, let's send the email



            // grab our filenames

            $files      = isset( $_POST['ffm_file_ids'] ) ? $_POST['ffm_file_ids'] : array();



            $file_list  = '';

            if( is_array( $files ) )

                foreach( $files as $filename ) {

                    $file_list .= FFM_DESTINATION_URL . $filename . "\n";



                      

                      $file_location = FFM_DESTINATION_DIR . $filename;

                      $wp_filetype = wp_check_filetype(basename($filename), null );

                      $attachment = array(

                         'post_mime_type' => $wp_filetype['type'],

                         'post_title' => ucfirst_utf8( preg_replace('/\.[^.]+$/', '', basename($filename)) ),

                         'guid' => FFM_DESTINATION_URL . $filename,

                         'post_content' => '',

                         'post_status' => 'inherit'

                      );

                      $attach_id = wp_insert_attachment( $attachment, $file_location );

                      // you must first include the image.php file

                      // for the function wp_generate_attachment_metadata() to work

                      require_once(ABSPATH . 'wp-admin/includes/image.php');

                      wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file_location ) );

                      //set attachment category

                      wp_set_post_terms( $attach_id, $_POST['mediacat'], 'mediacat' );

                      if ( $settings['auto_downloadable'] )

                            update_post_meta( $attach_id, '_downloadable', 1 );                                        

                }



            // grab our sender

            $email      = isset( $_POST['ffm_email'] ) ? mysql_real_escape_string( $_POST['ffm_email'] ) : '';



            // grab the submitted message

            $message    = isset( $_POST['ffm_message'] ) ? mysql_real_escape_string( $_POST['ffm_message'] ) : '';

            $message    = stripslashes( $message );



            // let's parse our email template

            $parsed = !empty( $settings['email_template'] ) ? $settings['email_template'] : "New files have been submitted by {@email}. The files submitted were:\n\n{@files}\n\nAdditionally, a message was provided:\n\n==========\n{@message}\n==========";



            // we'll grab our submitter IP

            $ip = $_SERVER['REMOTE_ADDR'];     



            $parsed = str_replace( '{@files}',      $file_list,                             $parsed );

            $parsed = str_replace( '{@email}',      $email,                                 $parsed );

            $parsed = str_replace( '{@message}',    $message,                               $parsed );

            $parsed = str_replace( '{@time}',       date( 'F jS, Y' ) . date( 'g:ia' ),     $parsed );

            $parsed = str_replace( '{@ip}',         $ip,                                    $parsed );



            $recipients     = !empty( $settings['email_recipients'] ) ? $settings['email_recipients'] : get_option( 'admin_email' );

            $subject        = !empty( $settings['email_subject'] ) ? $settings['email_subject'] : '[' . get_bloginfo( 'name' ) . '] New files uploaded';



            $headers = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>' . "\r\n";

            wp_mail( $recipients, $subject, $parsed, $headers );



            // lastly we'll output our success message

            $success_message = isset( $settings['success_message'] ) ? $settings['success_message'] : '<strong>Your files have been received.</strong> <a href="{@current}">Upload other files.</a>';

            $current = get_permalink();

            $success_message = str_replace( '{@current}', $current, $success_message );

            $output .= '<div class="front-file-manager-success">';

            $output .= wpautop( $success_message );

            $output .= '</div>';

        }

        else

        {

            $passcode    = isset( $_POST['ffm_passcode'] ) ? $_POST['ffm_passcode'] : '';



            $output     .= '<form action="" method="post" class="front-file-manager-flags">';



            // we're going to check to see if a passcode has been set and no passcode has been submitted (yet)

            // OR that a passcode has been set and the submitted passcode passes validation

            if( ( isset( $settings['passcode'] ) && !empty( $settings['passcode'] ) )           // passcode was set

                && empty( $passcode )                                                           // passcode was not submitted

                || ( ( !empty( $passcode ) && ( $passcode != $settings['passcode'] ) )          // invalid passcode submitted

                    && ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'feunonce' ) ) // but only if valid submission

                    )

            )

            {

                $output     .= '<input type="hidden" name="_wpnonce" id="_wpnonce" value="' . wp_create_nonce( 'feunonce' ) . '" />';



                $output     .= '<div class="front-file-manager-passcode">';

                if( !empty( $passcode ) )

                {

                    $output .= '<div class="front-file-manager-error passcode-error">';

                    $output .= __( 'Invalid passcode', 'frontfilemanager' );

                    $output .= '</div>';

                }

                $output     .= '<label for="ffm_passcode">' . __( 'Passcode', 'frontfilemanager' ) . '</label>';

                $output     .= '<input type="text" name="ffm_passcode" id="ffm_passcode" value="' . $passcode . '" />';

                $output     .= '</div>';

            }

            else

            {

                // we can go ahead and show the form



                // Plupload container

                $output     .= '<div class="front-file-manager"><p>' . __( "You browser does not have Flash, Silverlight, Gears, BrowserPlus or HTML5 support; upload is not available.", 'frontfilemanager' ) . '</p></div>';



                //Media Category

                $args = array(

                    'taxonomy' => 'mediacat',

                    'hide_empty' => 0,

                    'name' => 'mediacat',

                    'show_option_none' => __( 'Select Category', 'frontfilemanager' ),

                    'hierarchical' => 1,

                    'echo' => 0

                    );                                

                // Email

                $output     .= '<div class="front-file-manager-category-email">';

                $output     .= 'Media Category: ' . wp_dropdown_categories( $args ) . ' ';

                $output     .= '<div class="front-file-manager-message">';

                $output     .= '<label for="ffm_message">' . __( 'Message', 'frontfilemanager' ) . '</label>';

                $output     .= '<textarea name="ffm_message" class="ffm_message"></textarea>';

                $output     .= '</div>';

                $output     .= '<p><label for="ffm_email">' . __( 'Your Email Address', 'frontfilemanager' ) . '</label><input type="text" name="ffm_email" class="required_email" id="ffm_email" value="" />';

                $output     .= '</div></p>';



                // Message





                // we're going to flag the fact that we've got a valid submission

                $output     .= '<input type="hidden" name="_wpnonce" id="_wpnonce" value="' . wp_create_nonce( 'feuaccess' ) . '" />';

            }



            $output         .= '<div class="front-file-manager-submit"><input type="image" src="/wp-content/uploads/2012/06/addfiles4.png" />' . __( '', 'frontfilemanager' ) . '</div>';

            $output         .= '</form>';

        }



        $output .= '</div>';



        return $output;



    }





    /**

     * Settings API implementation for plugin settings

     *

     * @return void

     * @author Jonathan Christopher

     */

    function register_settings()

    {

        // flag our settings

        register_setting(

            FFM_PREFIX . 'settings',                                // group

            FFM_PREFIX . 'settings',                                // name of options

            array( 'FrontFileManager', 'validate_settings' )          // validation callback

        );



        add_settings_section(

            FFM_PREFIX . 'options',                                 // section ID

            'Options',                                              // title

            array( 'FrontFileManager', 'edit_options' ),              // display callback

            FFM_PREFIX . 'options'                                  // page name (do_settings_sections)

        );



        // submission passcode

        add_settings_field(

            FFM_PREFIX . 'passcode',                                // unique field ID

            'Submission Passcode',                                  // title

            array( 'FrontFileManager', 'edit_passcode' ),             // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // file size limit

        add_settings_field(

            FFM_PREFIX . 'max_file_size',                           // unique field ID

            'Max File Size',                                        // title

            array( 'FrontFileManager', 'edit_max_file_size' ),        // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // custom file extensions

        add_settings_field(

            FFM_PREFIX . 'custom_file_extensions',                  // unique field ID

            'Custom File Extension(s)',                             // title

            array( 'FrontFileManager', 'edit_file_extensions' ),      // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // email recipients

        add_settings_field(

            FFM_PREFIX . 'email_recipients',                        // unique field ID

            'Email Recipient(s)',                                   // title

            array( 'FrontFileManager', 'edit_email_recipients' ),     // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // email subject

        add_settings_field(

            FFM_PREFIX . 'email_subject',                           // unique field ID

            'Email Subject',                                        // title

            array( 'FrontFileManager', 'edit_email_subject' ),        // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // email template

        add_settings_field(

            FFM_PREFIX . 'email_template',                          // unique field ID

            'Email Template',                                       // title

            array( 'FrontFileManager', 'edit_email_template' ),       // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // success message

        add_settings_field(

            FFM_PREFIX . 'success_message',                         // unique field ID

            'Success Message',                                      // title

            array( 'FrontFileManager', 'edit_success_message' ),      // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );



        // success message

        add_settings_field(

            FFM_PREFIX . 'only_registered',                         // unique field ID

            'Only Registered Users',                                      // title

            array( 'FrontFileManager', 'only_registered' ),      // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );   

              

        // success message

        add_settings_field(

            FFM_PREFIX . 'auto_downloadable',                         // unique field ID

            'Auto downloadable upon upload',                                      // title

            array( 'FrontFileManager', 'auto_downloadable' ),      // input box display callback

            FFM_PREFIX . 'options',                                 // page name (as above)

            FFM_PREFIX . 'options'                                  // first arg to add_settings_section

        );        



    }





    /**

     * HTML output before settings fields

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_options()

    { ?>

        <p style="padding-left:10px;"><?php _e( "An email is sent each time a Front File Manager is submitted. You can customize the recipients, the email itself, and other options here.", "frontfilemanager" ); ?></p>

    <? }





    /**

     * Validates options

     *

     * @param $input

     * @return array $input Array of all associated options

     * @author Jonathan Christopher

     */

    function validate_settings($input)

    {

        return $input;

    }





    /**

     * Outputs the HTML used for the passcode field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_passcode()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="text" class="regular-text" name="<?php echo FFM_PREFIX; ?>settings[passcode]" value="<?php echo !empty( $settings['passcode'] ) ? $settings['passcode'] : ''; ?>" /> <span class="description">Require this passcode to submit a Front File Manager. <strong>Leave empty to disable.</strong></span>

    <?php }





    /**

     * Outputs the HTML used for the max file size field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_max_file_size()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="text" class="small-text" name="<?php echo FFM_PREFIX; ?>settings[max_file_size]" value="<?php echo !empty( $settings['max_file_size'] ) ? intval( $settings['max_file_size'] ) : '10'; ?>" /> <span class="description">MB</span>

    <?php }





    /**

     * Outputs the HTML used for the custom file extensions field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_file_extensions()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="text" class="regular-text" name="<?php echo FFM_PREFIX; ?>settings[custom_file_extensions]" value="<?php echo !empty( $settings['custom_file_extensions'] ) ? $settings['custom_file_extensions'] : ''; ?>" /> <span class="description">Comma separated, no period (e.g. html,css)</span>

    <?php }





    /**

     * Outputs the HTML used for the email recipient(s) field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_email_recipients()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="text" class="regular-text" name="<?php echo FFM_PREFIX; ?>settings[email_recipients]" value="<?php echo !empty( $settings['email_recipients'] ) ? $settings['email_recipients'] : get_option( 'admin_email' ); ?>" /> <span class="description">Separate multiple email addresses with commas</span>

    <?php }





    /**

     * Outputs the HTML used for the email subject field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_email_subject()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="text" class="regular-text" name="<?php echo FFM_PREFIX; ?>settings[email_subject]" value="<?php echo !empty( $settings['email_subject'] ) ? $settings['email_subject'] : '[' . get_bloginfo( 'name' ) . '] New files uploaded'; ?>" />

    <?php }





    /**

     * Outputs the HTML used for the email template field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_email_template()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <textarea rows="10" cols="50" class="large-text code" name="<?php echo FFM_PREFIX; ?>settings[email_template]"><?php echo !empty( $settings['email_template'] ) ? $settings['email_template'] : "New files have been submitted by {@email}. The files submitted were:\n\n{@files}\n\nAdditionally, a message was provided:\n\n==========\n{@message}\n=========="; ?></textarea>

        <div class="front-file-manager-tags">

            <a id="feu-tags-toggle" href="#feu-tags"><?php _e( "Tags Available" ); ?></a>

            <div id="feu-tags" >

                <table>

                    <thead>

                        <tr>

                            <th><?php _e( "Tag", "frontfilemanager" ); ?></th>

                            <th><?php _e( "Output", "frontfilemanager" ); ?></th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td><code>{@files}</code></td>

                            <td><?php _e( "List of files uploaded", "frontfilemanager" ); ?></td>

                        </tr>

                        <tr>

                            <td><code>{@email}</code></td>

                            <td><?php _e( "Email address of submitter", "frontfilemanager" ); ?></td>

                        </tr>

                        <tr>

                            <td><code>{@message}</code></td>

                            <td><?php _e( "Message from submitter", "frontfilemanager" ); ?></td>

                        </tr>

                        <tr>

                            <td><code>{@time}</code></td>

                            <td><?php _e( "The time the email is sent", "frontfilemanager" ); ?></td>

                        </tr>

                        <tr>

                            <td><code>{@ip}</code></td>

                            <td><?php _e( "The submitters IP address", "frontfilemanager" ); ?></td>

                        </tr>

                        <tr>

                            <td><code>{@current}</code></td>

                            <td><?php _e( "Current page URL", "frontfilemanager" ); ?></td>

                        </tr>                        

                    </tbody>

                </table>

            </div>

        </div>

        <script type="text/javascript">

            jQuery(document).ready(function(){

                jQuery('#feu-tags').hide();

                jQuery('a#feu-tags-toggle').click(function(){

                    jQuery('#feu-tags').slideToggle();

                    return false;

                });

            });

        </script>

        <style type="text/css">

            #feu-tags { max-width:500px; }

            #feu-tags table { width:100%; }

            #feu-tags table thead th { font-weight:bold; }

            #feu-tags table th,

            #feu-tags table td { padding-left:0; padding-bottom:2px; }

            #feu-tags table td code { font-size:1em; }

        </style>

    <?php }





    /**

     * Outputs the HTML used for the success message field

     *

     * @return void

     * @author Jonathan Christopher

     */

    function edit_success_message()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <textarea rows="10" cols="50" class="large-text code" name="<?php echo FFM_PREFIX; ?>settings[success_message]"><?php echo !empty( $settings['success_message'] ) ? $settings['success_message'] : '<strong>Your files have been received.</strong> <a href="{@current}">Upload other files.</a>'; ?></textarea>

    <?php }



    /**

     * Outputs the HTML used for the auto downloadable field

     *

     * @return void

     * @author Ján Bočínec

     */

    function only_registered()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="checkbox" name="<?php echo FFM_PREFIX; ?>settings[only_registered]" value="1" class"only_registered" <?php checked( $settings['only_registered'], 1 ); ?>/>

    <?php }



    /**

     * Outputs the HTML used for the auto downloadable field

     *

     * @return void

     * @author Ján Bočínec

     */

    function auto_downloadable()

    {

        $settings = get_option( FFM_PREFIX . 'settings' );

        ?>

        <input type="checkbox" name="<?php echo FFM_PREFIX; ?>settings[auto_downloadable]" value="1" class"auto_downloadable" <?php checked( $settings['auto_downloadable'], 1 ); ?>/>

    <?php }





    /**

     * Enqueue Front File Manager assets

     *

     * @return void

     * @author Jonathan Christopher

     */

    function assets()

    {

        // add options menu

        add_options_page( 'Settings', 'Front File Manager', 'manage_options', __FILE__, array( 'FrontFileManager', 'admin_screen_options' ) );

    }





    /**

     * Enqueue the Plupload assets

     *

     * @return void

     * @author Jonathan Christopher

     */

    function assets_public()

    {

        wp_enqueue_script( 'jquery' );

        wp_enqueue_script(

            'browserplus'

            ,'http://bp.yahooapis.com/2.4.21/browserplus-min.js'

            ,null

            ,FFM_VERSION

            ,false

        );

        wp_enqueue_script(

            'feu-plupload'

            ,FFM_URL . '/lib/plupload/js/plupload.full.js'

            ,'jquery'

            ,FFM_VERSION

            ,false

        );



        // plupload localization

        $locale = substr( get_locale(), 0, 2 );

        if( 'en' != $locale )

        {

            $plupload_translations = array( 'sk', 'cs', 'da', 'de', 'es', 'fi', 'fr', 'it', 'ja', 'lv', 'nl', 'pt-br', 'ru', 'sv' );



            if( in_array( $locale, $plupload_translations ) )

            {

                wp_enqueue_script(

                    'feu-plupload-queue-i18n'

                    ,FFM_URL . '/lib/plupload/js/i18n/' . $locale . '.js'

                    ,'jquery'

                    ,FFM_VERSION

                    ,false

                );

            }

        }



        wp_enqueue_script(

            'feu-plupload-queue'

            ,FFM_URL . '/lib/plupload/js/jquery.plupload.queue/jquery.plupload.queue.js'

            ,'jquery'

            ,FFM_VERSION

            ,false

        );



        wp_enqueue_style(

            'feu-plupload'

            ,FFM_URL . '/lib/plupload/js/jquery.plupload.queue/css/jquery.plupload.queue.css'

            ,FFM_VERSION

        );

    }





    /**

     * Initializes Plupload on the front end

     *

     * @return void

     * @author Jonathan Christopher

     */

    function init_plupload()

    { ?>

    <script type="text/javascript">

        jQuery(document).ready(function() {

            jQuery(".front-file-manager").each(function(){

                jQuery(this).pluploadQueue({

                    // General settings

                    runtimes : 'html5,flash,gears,silverlight,browserplus',

                    url : '<?php echo FFM_URL; ?>/upload.php',



                    <?php

                        $settings = get_option( FFM_PREFIX . 'settings' );



                        if( !empty( $settings ) && isset( $settings['max_file_size'] ) )

                        {

                            $max_file_size = intval( $settings['max_file_size'] );

                        }

                        else

                        {

                            $max_file_size = '10';

                        }

                    ?>



                    max_file_size : '<?php echo $max_file_size; ?>mb',

                    chunk_size : '1mb',

                    unique_names : false,



                    // Resize images on clientside if we can

                    // resize : {width : 1024, height : 768, quality : 90},



                    // Specify what files to browse for

                    filters : [

                        {title : "Image files", extensions : "jpg,jpeg,gif,png"},

                        {title : "PDF files", extensions : "pdf"},

                        {title : "Office files", extensions : "doc,docx,xls,txt,rtf"},

                        {title : "Zip files", extensions : "zip"}<?php if( isset( $settings['custom_file_extensions'] ) && !empty( $settings['custom_file_extensions'] ) ) : ?>,

                        {title : "Other", extensions : "<?php echo $settings['custom_file_extensions']; ?>"}<?php endif; ?>

                    ],



                    // Flash settings

                    flash_swf_url : '<?php echo FFM_URL; ?>/lib/plupload/js/plupload.flash.swf',



                    // Silverlight settings

                    silverlight_xap_url : '<?php echo FFM_URL; ?>/lib/plupload/js/plupload.silverlight.xap',



                    // Post init events, bound after the internal events

                    init : {

                        FileUploaded: function(up, file, info) {

                            // Called when a file has finished uploading

                            var ffm_latestID = info.response;

                            jQuery('.front-file-manager-flags').append('<input type="hidden" name="ffm_file_ids[]" value="' + ffm_latestID + '" />');

                        }



                    }

                });

                jQuery("a.plupload_start").remove();        // we don't want uploads without form submission

            });



            // Client side form validation

            jQuery('.front-file-manager-parent form').submit(function(e) {



                var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;



                // first and foremost we'll validate the email

                if( jQuery("#ffm_email").length ){

                    if( !re.test(jQuery("#ffm_email").val()) ){

                        alert("<?php _e( "You must enter a valid email address.", "frontfilemanager" ); ?>");

                        jQuery("#ffm_email").focus();

                        return false;

                    }

                }



                var feuref = jQuery(this);

                var uploader = feuref.parents('.front-file-manager-parent').find('.front-file-manager').pluploadQueue();



                // Files in queue upload them first

                if (uploader.files.length > 0) {

                    // When all files are uploaded submit form

                    uploader.bind('StateChanged', function() {

                        if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {

                            jQuery('.front-file-manager-flags')[0].submit();

                        }

                    });

                    uploader.start();

                } else {

                    alert('<?php _e( "You must queue at least one file.", "frontfilemanager" ); ?>');

                }

                return false;

            });

        });

    </script>

    <?php }





    /**

     * Callback for Options screen

     *

     * @return void

     * @author Jonathan Christopher

     */

    function admin_screen_options()

    {

        include 'front-file-manager-options.php';

    }





    /**

     * Modifies the plugin meta line on the WP Plugins page

     *

     * @param $plugin_meta

     * @param $plugin_file

     * @return array $plugin_meta Array of plugin meta data

     * @author Jonathan Christopher

     */

    function filter_plugin_row_meta( $plugin_meta, $plugin_file )

    {

        if( strstr( $plugin_file, 'front-file-manager' ) )

        {

            $plugin_meta[3] = 'Plugin from the <a title="WordPress Slovensko" href="http://wp.sk/">Slovak Community</a>';

            return $plugin_meta;

        }

        else

        {

            return $plugin_meta;

        }

    }



}



$front_end_upload = new FrontFileManager();



function ucfirst_utf8($stri){ 

    if($stri{0}>="\xc3") 

        return (($stri{1}>="\xa0")? 

        ($stri{0}.chr(ord($stri{1})-32)): 

        ($stri{0}.$stri{1})).substr($stri,2); 

    else return ucfirst($stri); 

} 