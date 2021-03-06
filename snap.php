<?php

require_once('./config.php');
require_once('./lib/UrlHandler.php');

/**
 * Grab a snapshot of this WordPress blog.
 */
class Snap_Command extends WP_CLI_Command {

    public $uh;
    function Snap_Command() {
        $this->uh = new \wpsnapshot\UrlHandler(get_bloginfo('url'), TARGET_FOLDER);
    }

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
            print $link['url'] . " => " . $link['name'][0] . $link['name'][1] . "\n";
        }

        WP_CLI::success( "Finished listing for: " . get_bloginfo('name') );
    }

    /**
     * Create an archive/copy of all blog URLs as shown using snap list
     *
     * ## OPTIONS
     *
     * [<url>]
     * : Optional URL to process instead of using output from list
     * 
     * ## EXAMPLES
     * 
     *     wp snap shot
     *
     */
    function shot( $args, $assoc_args ) {
        $url = isset($args[0]) ? $args[0] : FALSE;
        if ($url) {
            $links = array(
                array(
                    'url'   => $url,
                    'name'  => array('SINGLE/', 'index.html'),
                )
            );
        } else {
            $links = Snap_Command::_list_all();
        }
        $site_url = get_bloginfo('url');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // We want to get the respone

        $success = 0;
        $site_resources = array();
        foreach ($links as $link_and_name) {
            $link = $link_and_name['url'];

            curl_setopt($ch, CURLOPT_URL, $link);    // The url to get links from
            $result = curl_exec($ch);

            if (COPY_SRC) {
                print "Scanning: $link\n";
                // grab any site_resources links (src="\w+" effectively) We'll
                // handle whether they are appropriate to copy as part of the
                // snapshot later
                preg_match_all(
                    '#<[^>]+src\s*=\s*[\'"]?([^>\'"]+)[\'"]?[^>]*>#i',
                    $result, $matches
                );
                if (isset($matches[1])) {
                    foreach ($matches[1] as $url) {
                        if (strpos($url, $site_url) !== false){
                            array_push($site_resources, $url);
                        }
                    }
                } // end if match found

                // Grab any CSS files
                preg_match_all(
                    '#<link[^>]+href\s*=\s*[\'"]?([^>\'"]+)[\'"]?[^>]*>#i',
                    $result, $matches
                );
                if (isset($matches[1])) {
                    foreach ($matches[1] as $url) {
                        if (strpos($url, $site_url) !== false){
                            array_push($site_resources, $url);
                        }
                    }
                } // end if match found
            } // end if copying source

            if (REWRITE_LINKS) {
                $result = str_ireplace(SRC_OLD, SRC_TO, $result);
            }

            if ($this->uh->save_file($result, $link_and_name['name'])) {
                $success++;
            }
        }

        if (COPY_SRC) {
            // Ensure we only have unique resource URLs to grab. It's likely
            // that a header image or similar will have been included for every
            // URL accessed
            $site_resources = array_unique($site_resources);

            foreach ($site_resources as $r) {
                print "Found resource: $r\n";
                curl_setopt($ch, CURLOPT_URL, $r);    // The url to get links from
                $result = curl_exec($ch);

                if (preg_match('/\.css(\?.*)?$/i', $r)) {
                    $css_resources = array();
                    // Try grab images from a CSS file
                    print "Deep CSS checking\n";
                    preg_match_all(
                        '#:\s*url\s*\(\s*[\'"]?([^\)\'"]+)[\'"]?\s*\)#i',
                        $result, $matches
                    );
                    if (isset($matches[1])) {
                        foreach ($matches[1] as $url) {
                            if (!preg_match('/data:/', $url) && !preg_match('/base64/', $url)) {
                                array_push($css_resources, $url);
                            }
                        }
                    } // end if match found

                    foreach ($css_resources as $r) {
                        print "Found CSS resource: $r\n";
                        curl_setopt($ch, CURLOPT_URL, $r);    // The url to get links from
                        $result = curl_exec($ch);
                        $this->uh->save_file($result, $this->uh->generate_local_name_rel($r, $url));
                    }
                }

                $this->uh->save_file($result, $this->uh->generate_local_name($r));
            }
        }

        WP_CLI::success( "Took a snapshot of " . $success . "/" . strval(count($links)+1) . " URLs for " . get_bloginfo('name') );
    }

    /**
     * Query WordPress for different post types, then loop to get each public
     * post and permalink.
     *
     * Returns an array which has an array of 'url' to 'rewrite', where rewrite
     * is the local directory and file path (excluding the prefix set in the config)
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
                array_push($links, $archive);
            }

            $args = array(
                'numberposts' => -1,
                'post_type'   => $type,
                'post_status' => 'publish',
            ); 

            $dates = array(); // only filled by the post type
            $posts = get_posts( $args );
            foreach ( $posts as $post ) {
                array_push($links, get_permalink($post));

                // Grab the year/month/day for all posts
                if ($type == 'post') {
                    $post_data = get_post($post);

                    // Grab date pages also
                    $date_parts = explode('-', substr($post_data->post_date,0,10));
                    if (count($date_parts) !== 3) {
                        continue;
                    } else {
                        list($year, $month, $date) = $date_parts;
                    }

                    if (!array_key_exists($year, $dates)) {
                        $dates[$year] = array();
                    }
                    if (!array_key_exists($month, $dates[$year])) {
                        $dates[$year][$month] = array();
                    }
                    if (!in_array($date, $dates[$year][$month])) {
                        array_push($dates[$year][$month], $date);
                    }
                }
            }

            // Cycle through years/months/days and get appropriate links
            foreach ($dates as $year => $months) {
                array_push($links, get_year_link($year));
                foreach ($months as $month => $dates) {
                    array_push($links, get_month_link($year, $month));
                    foreach ($dates as $date) {
                        array_push($links, get_day_link($year, $month, $date));
                    }
                }
            }

            // How to handle pagination...
        }

        // each $links entry so far needs a standard rewrite...
        for ($i = 0; $i < count($links); $i++) {
            $links[$i] = array(
                'url'   => $links[$i],
                'name'  => $this->uh->generate_local_name($links[$i]),
            );
        }

        // Include the 404 template
        array_push($links, array(
            'url'   => get_bloginfo('url') . '?error=404',
            'name'  => array('/404/', 'index.html')
        ));

        return $links;
    }

}

WP_CLI::add_command( 'snap', 'Snap_Command' );
