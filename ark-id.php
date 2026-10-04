<?php
/**
 * Plugin Name: ARK Identifier Resolver
 * Plugin URI: https://github.com/jkunze/ark-id
 * Description: Resolves ARK identifiers in the format /ark:xxxxx/name and /ark:xxxxx/name/qualifier with optional iframe embedding or HTTP 302/303 redirects. Also handles classic-form ARKs (e.g., /ark:/xxxxx/name).
 * Version: 2.1.0
 * Author: Josefrank Pernalete Lugo, John Kunze
 * Author URI: https://github.com/josefrankpl-hue, https://github.com/jkunze
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: ark-id
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

final class ARK_ID_Resolver {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_ark_request'));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^ark:/?([0-9]{5})/([a-zA-Z0-9\-_.~%+]+)/([a-zA-Z0-9\-_.~%+]+)/?$',
            'index.php?ark_naan=$matches[1]&ark_name=$matches[2]&ark_qualifier=$matches[3]',
            'top'
        );

        add_rewrite_rule(
            '^ark:/?([0-9]{5})/([a-zA-Z0-9\-_.~%+]+)/?$',
            'index.php?ark_naan=$matches[1]&ark_name=$matches[2]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'ark_naan';
        $vars[] = 'ark_name';
        $vars[] = 'ark_qualifier';

        return $vars;
    }

    public function handle_ark_request() {
        $naan = get_query_var('ark_naan');
        $name = get_query_var('ark_name');
        $qualifier = get_query_var('ark_qualifier');

        if (empty($naan) || empty($name)) {
            return;
        }

        $records = get_option('ark_id_records', array());
        $lookup_key = strtolower($naan . '_' . $name . (!empty($qualifier) ? '_' . $qualifier : ''));

        if (!isset($records[$lookup_key]) || empty($records[$lookup_key]['url'])) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            include get_query_template('404');
            exit;
        }

        $record = $records[$lookup_key];
        $destination_url = esc_url_raw($record['url']);
        $mode = isset($record['mode']) ? $record['mode'] : 'iframe';
        $redirect_type = isset($record['type']) ? intval($record['type']) : 302;

        if ('iframe' === $mode) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-cache, must-revalidate');
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>ARK Resolver</title>
                <style>
                    html, body {
                        margin: 0;
                        padding: 0;
                        width: 100%;
                        height: 100%;
                        overflow: hidden;
                        background: #fff;
                    }
                    iframe {
                        width: 100%;
                        height: 100%;
                        border: 0;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <iframe src="<?php echo esc_url($destination_url); ?>" title="ARK Resource">
                    Your browser does not support embedded frames. <a href="<?php echo esc_url($destination_url); ?>">Open the resource</a>.
                </iframe>
            </body>
            </html>
            <?php
            exit;
        }

        if (!in_array($redirect_type, array(302, 303), true)) {
            $redirect_type = 302;
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        wp_redirect($destination_url, $redirect_type);
        exit;
    }

    public function add_admin_menu() {
        add_options_page(
            'ARK Identifier Resolver',
            'ARK Resolver',
            'manage_options',
            'ark-id-resolver',
            array($this, 'render_admin_page')
        );
    }

    public function register_settings() {
        register_setting('ark_id_resolver_group', 'ark_id_records');
        
        register_setting('ark_id_resolver_group', 'ark_id_classic', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('ark_id_resolver_group', 'ark_id_naan', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('ark_id_resolver_group', 'ark_id_local_resolver', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $default_naan = get_option('ark_id_naan', '');
        $default_local_resolver = get_option('ark_id_local_resolver', '');
        if (!preg_match('/\/$'), $default_local_resolver) {
            $default_local_resolver = $default_local_resolver . '/';
        }
        if (
            isset($_POST['ark_id_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ark_id_nonce'])), 'ark_id_save_record')
        ) {
            $records = get_option('ark_id_records', array());

            if (isset($_POST['delete_ark'])) {
                $delete_key = sanitize_text_field(wp_unslash($_POST['delete_ark']));
                unset($records[$delete_key]);
                update_option('ark_id_records', $records);
                echo '<div class="notice notice-success"><p>ARK record deleted.</p></div>';
            } else {
                $naan = sanitize_text_field(wp_unslash($_POST['ark_naan'] ?? $default_naan));
                $name = sanitize_text_field(wp_unslash($_POST['ark_name'] ?? ''));
                $qualifier = sanitize_text_field(wp_unslash($_POST['ark_qualifier'] ?? ''));
                $target_url = esc_url_raw(wp_unslash($_POST['ark_url'] ?? ''));
                $mode = sanitize_text_field(wp_unslash($_POST['ark_mode'] ?? 'iframe'));
                $type = intval($_POST['ark_type'] ?? 302);

                if (!preg_match('/^https?:\/\//', $target_url)) {
                    $target_url = $default_local_resolver . $target_url;
                }
                if (!preg_match('/^[0-9]{5}$/', $naan)) {
                    echo '<div class="notice notice-error"><p>Error: NAAN must be exactly 5 numeric digits.</p></div>';
                } elseif (!preg_match('/^[a-zA-Z0-9\-_.~%+]+$/', $name)) {
                    echo '<div class="notice notice-error"><p>Error: Assigned Name contains invalid characters.</p></div>';
                } elseif (!empty($qualifier) && !preg_match('/^[a-zA-Z0-9\-_.~%+]+$/', $qualifier)) {
                    echo '<div class="notice notice-error"><p>Error: Qualifier contains invalid characters.</p></div>';
                } elseif (empty($target_url) || filter_var($target_url, FILTER_VALIDATE_URL) === false) {
                    echo '<div class="notice notice-error"><p>Error: Please provide a valid destination URL.</p></div>';
                } else {
                    $key = strtolower($naan . '_' . $name . (!empty($qualifier) ? '_' . $qualifier : ''));
                    $records[$key] = array(
                        'naan' => $naan,
                        'name' => $name,
                        'qualifier' => $qualifier,
                        'url' => $target_url,
                        'mode' => $mode,
                        'type' => $type,
                    );

                    update_option('ark_id_records', $records);
                    echo '<div class="notice notice-success"><p>ARK record saved successfully.</p></div>';
                }
            }
        }

        $records = get_option('ark_id_records', array());
        $classic = get_option('ark_id_classic', '');
        ?>
        <div class="wrap">
            <h1>ARK Identifier Resolver</h1>
            <p>Manage IDs of the form <code>/ark:xxxxx/assigned-name</code> or <code>/ark:xxxxx/assigned-name/qualifier</code>.</p>

            <form method="post" action="" style="background: #fff; padding: 20px; border: 1px solid #dcdcde; max-width: 760px; margin-bottom: 24px;">
                <?php wp_nonce_field('ark_id_save_record', 'ark_id_nonce'); ?>

                <h2>Add a new ARK mapping</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ark_naan">NAAN (5 digits)</label></th>
                        <td>
                            <input type="text" id="ark_naan" name="ark_naan" class="regular-text" maxlength="5" pattern="[0-9]{5}" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ark_name">Assigned Name</label></th>
                        <td>
                            <input type="text" id="ark_name" name="ark_name" class="regular-text" required>
                            <p class="description">Allows letters, numbers, and the characters <code>- _ . ~ % +</code>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ark_qualifier">Qualifier (optional)</label></th>
                        <td>
                            <input type="text" id="ark_qualifier" name="ark_qualifier" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ark_url">Destination URL</label></th>
                        <td>
                            <input type="url" id="ark_url" name="ark_url" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ark_mode">Behavior</label></th>
                        <td>
                            <select id="ark_mode" name="ark_mode">
                                <option value="iframe">Keep ARK URL in browser (embedded page)</option>
                                <option value="redirect">HTTP redirect (302/303)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ark_type">Redirect type</label></th>
                        <td>
                            <select id="ark_type" name="ark_type">
                                <option value="302">302 Found</option>
                                <option value="303">303 See Other</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save ARK Record'); ?>
            </form>

            <h2>Existing records</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ARK URL</th>
                        <th>Target URL</th>
                        <th>Mode</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)) : ?>
                        <tr><td colspan="4">No ARK records saved yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($records as $key => $data) : ?>
                            <?php $ark_path = 'ark:' . $classic . $data['naan'] . '/' . $data['name'] . (!empty($data['qualifier']) ? '/' . $data['qualifier'] : ''); ?>
                            <?php $full_ark_url = home_url('/' . $ark_path); ?>
                            <tr>
                                <td><a href="<?php echo esc_url($full_ark_url); ?>" target="_blank"><code><?php echo esc_html($ark_path); ?></code></a></td>
                                <td><a href="<?php echo esc_url($data['url']); ?>" target="_blank"><?php echo esc_html($data['url']); ?></a></td>
                                <td>
                                    <?php echo esc_html('iframe' === $data['mode'] ? 'Embedded page' : 'HTTP ' . intval($data['type'])); ?>
                                </td>
                                <td>
                                    <form method="post" action="" style="display:inline;">
                                        <?php wp_nonce_field('ark_id_save_record', 'ark_id_nonce'); ?>
                                        <input type="hidden" name="delete_ark" value="<?php echo esc_attr($key); ?>">
                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Delete this ARK record?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

ARK_ID_Resolver::instance();

register_activation_hook(__FILE__, function () {
    ARK_ID_Resolver::instance()->activate();
});

register_deactivation_hook(__FILE__, function () {
    ARK_ID_Resolver::instance()->deactivate();
});
