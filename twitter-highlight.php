<?php
/*
 * Plugin Name: Twitter Highlight
 * Plugin URI: http://osiux.ws/2010/04/13/twitter-highlight/
 * Description: Convert twitter usernames, hashtags and lists in pages, posts or comments to a twitter link.
 * Version: 1.1
 * Author: Eduardo Reveles
 * Author URI: http://osiux.ws
 * Text-Domain: twitter-highlight
*/

// Initialize plugin
TwitterHighlight::init();

class TwitterHighlight {
    // Static function to initialize plugin
    public static function init() {
        $th = new TwitterHighlight;
        $th->run();
    }

    // Register filters, functions, hooks and load languages
    public function run() {
        // Get plugin options
        $options = get_option('twitterhl_options');

        // Load language
        load_plugin_textdomain('twitter-highlight', false, 'twitter-highlight/i18n');

        // Hooks
        register_activation_hook(__FILE__, array($this, 'install'));
        register_deactivation_hook(__FILE__, array($this, 'uninstall'));

        // Actions
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Filters
        if ((bool) $options['inpost'] || (bool) $options['inpage']) {
            add_filter('the_content', array($this, 'highlight'));
        }

        if ((bool) $options['inrss']) {
            add_filter('the_content_rss', array($this, 'highlight'));
            add_filter('comment_text_rss', array($this, 'highlight'));
        }

        if ((bool) $options['incomment']) {
            add_filter('comment_text', array($this, 'highlight_comment'));
        }
    }

    // When installed, put some default options
    public function install() {
        $options = array('inpost'       =>  true,
                         'inpage'       =>  true,
                         'incomment'    =>  true,
                         'inrss'        =>  true,
                         'nofollow'     =>  true,
                         'newpage'      =>  false);

        update_option('twitterhl_options', $options);
    }

    // Delete options on uninstall
    public function uninstall() {
        delete_option('twitterhl_options');
    }

    // Wrapper function to make highlight in comments
    public function highlight_comment($content) {
        return $this->highlight($content, true);
    }

    public function highlight($content, $comment = false) {
        $options = get_option('twitterhl_options');

        if (in_the_loop() && !$comment) {
            if (is_page() && !(bool) $options['inpage']) {
                return $content;
            }

            if (!is_page() && !(bool) $options['inpost']) {
                return $content;
            }
        }

        $attr = '';
        $attr .= (bool) $options['nofollow'] ? ' rel="nofollow"' : '' ;
        $attr .= (bool) $options['newpage'] ? ' target="_blank"' : '' ;

        // Do the magic
        $content = preg_replace("/\B@(\w+(?!\/))\b/i", '<a href="https://twitter.com/\\1"'.$attr.'>&commat;\\1</a>', $content); // Username
        $content = preg_replace("/\B(?<![=\/])#([\w]+[a-z]+([0-9]+)?)(?![^<]*>)/i", '<a href="https://twitter.com/search?q=%23\\1"'.$attr.'>#\\1</a>', $content); // Hashtag
        $content = preg_replace("/\B@([\w]+\/[\w]+)(?![^<]*>)/i", '<a href="https://twitter.com/\\1"'.$attr.'>&commat;\\1</a>', $content); // List

        return $content;
    }

    public function menu() {
        add_options_page(__('Twitter Highlight', 'twitter-highlight'), __('Twitter Highlight', 'twitter-highlight'), 'administrator', 'twitter-highlight', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('twitterhl-options', 'twitterhl_options', array($this, 'validate_settings'));
    }

    public function validate_settings($input) {
        $input['nofollow']      =   (bool) $input['nofollow'];
        $input['inpost']        =   (bool) $input['inpost'];
        $input['inpage']        =   (bool) $input['inpage'];
        $input['inrss']         =   (bool) $input['inrss'];
        $input['incomment']     =   (bool) $input['incomment'];
        $input['newpage']       =   (bool) $input['newpage'];

        return $input;
    }

    public function settings_page() {
        $options = get_option('twitterhl_options');
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br></div>
            <h2><?php echo __('Twitter Highlight Settings', 'twitter-highlight'); ?></h2>
            <form name="settings" method="post" action="options.php">
                <?php settings_fields('twitterhl-options'); ?>
                <table class="form-table">
                    <tr valign="ẗop">
                        <th scope="row"><label for="nofollow"><?php echo __('Use <em>rel="nofollow"</em> attribute:', 'twitter-highlight'); ?></label></th>
                        <td><input name="twitterhl_options[nofollow]" type="checkbox" id="nofollow" value="1"<?php checked('1', $options['nofollow']); ?> /></td>
                    </tr>
                    <tr valign="ẗop">
                        <th scope="row"><label for="newpage"><?php echo __('Open links on a new page:', 'twitter-highlight'); ?></label></th>
                        <td><input name="twitterhl_options[newpage]" type="checkbox" id="newpage" value="1"<?php checked('1', $options['newpage']); ?> /></td>
                    </tr>
                </table>
                <h3>Replace options</h3>
                <p><?php echo __('You can choose where in your blog you want the plugin to replace links.', 'twitter-highlight'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            Replace in:
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Replace in</span></legend>
                            </fieldset>
                            <label for="inpost">
                                <input name="twitterhl_options[inpost]" type="checkbox" id="inpost" value="1"<?php checked('1', $options['inpost']); ?> />
                                <?php echo __('Posts', 'twitter-highlight'); ?>
                            </label>
                            <br>
                            <label for="inpage">
                                <input name="twitterhl_options[inpage]" type="checkbox" id="inpage" value="1"<?php checked('1', $options['inpage']); ?> />
                                <?php echo __('Pages', 'twitter-highlight'); ?>
                            </label>
                            <br>
                            <label for="incomment">
                                <input name="twitterhl_options[incomment]" type="checkbox" id="incomment" value="1"<?php checked('1', $options['incomment']); ?> />
                                <?php echo __('Comments', 'twitter-highlight'); ?>
                            </label>
                            <br>
                            <label for="inrss">
                                <input name="twitterhl_options[inrss]" type="checkbox" id="inrss" value="1"<?php checked('1', $options['inrss']); ?> />
                                <?php echo __('RSS Feed', 'twitter-highlight'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="change_settings" class="button-primary" value="<?php echo __('Update Settings', 'twitter-highlight'); ?>" /></p>
            </form>
        </div>
        <?php
    }
}