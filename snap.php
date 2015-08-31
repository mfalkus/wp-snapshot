<?php

// Grab our config file...
require_once('./config.php');

/**
 * Grab a snapshot of this WordPress blog.
 */
class Snap_Command extends WP_CLI_Command {

    /**
     * List blog URLs
     * 
     * ## EXAMPLES
     * 
     *     wp snap list
     *
     * @alias list
     */
    function _list( $args, $assoc_args ) {
        $links = Snap_Command::_list_all();

        foreach ($links as $link) {
            print $link . "\n";
        }

        WP_CLI::success( "Finished listing for: " . get_bloginfo('name') );
    }

    /**
     * Create an archive/copy of all blog URLs as shown using snap list
     * 
     * ## EXAMPLES
     * 
     *     wp snap shot
     *
     */
    function shot( $args, $assoc_args ) {
        $links = Snap_Command::_list_all();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // We want to get the respone

        $success = 0;
        foreach ($links as $link) {
            curl_setopt($ch, CURLOPT_URL, $link);    // The url to get links from
            $result = curl_exec($ch);
            // Check for success here

            // Setup directory if not exists
            $comps = parse_url($link);
            $path = $comps['path'];
            $output_path = TARGET_FOLDER . $path;
            if (!file_exists($output_path)) {
                mkdir($output_path, 0777, true);
            }

            $test = file_put_contents($output_path . 'index.html', $result);
            if ($test !== FALSE) {
                $success++;
            } else {
                print ("Unable to write to " . $output_path . "index.html\n");
            }
        }

        // Include the 404 template
        curl_setopt($ch, CURLOPT_URL, get_bloginfo('url') . '?error=404');    // The url to get links from
        $result = curl_exec($ch);

        $output_path = TARGET_FOLDER . '/404/';
        if (!file_exists($output_path)) {
            mkdir($output_path, 0777, true);
        }

        $test = file_put_contents($output_path . 'index.html', $result);
        if ($test !== FALSE) {
            $success++;
        } else {
            print ("Unable to write to " . $output_path . "index.html\n");
        }

        WP_CLI::success( "Took a snapshot of " . $success . "/" . strval(count($links)+1) . " URLs for " . get_bloginfo('name') );
    }

    /**
     * Query WordPress for different post types, then loop to get each public
     * post and permalink.
     *
     * Doesn't include 'special' cases, i.e. 404 pages
     */
    private function _list_all() {

        $post_types = get_post_types( array (
            'public'    => true, // only interested in things we can link to!
            '_builtin'  => true, // interested in post/pages plus custom stuff
        ) );

        // For each type, query to get all public 'posts'
        $links = array();
        foreach ($post_types as $type) {
            // Start with the archive link

            $archive = get_post_type_archive_link($type);
            if ($archive) {
                array_push($links, 'ARCHIVE' . $archive);
            }

            $args = array(
                'numberposts' => -1,
                'post_type'   => $type,
                'post_status' => 'publish',
            ); 
            $posts = get_posts( $args );
            foreach ( $posts as $post ) {
                array_push($links, get_permalink($post));
            }

            // How to handle year/month/date links?
            // How to handle pagination...
        }

        return $links;
    }
}

WP_CLI::add_command( 'snap', 'Snap_Command' );
