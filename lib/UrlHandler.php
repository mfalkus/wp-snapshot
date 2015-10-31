<?php
namespace wpsnapshot;

class UrlHandler {

    public $blogurl = '';
    public $output_base = '';
    function UrlHandler($blogurl, $output_base) {
        $this->blogurl = $blogurl;
        $this->output_base = $output_base;
    }

    /**
     * Save the given contents to the directory and the filename supplied.
     * This will create the folder if required.
     *
     * Returns true if successfully, false otherwise.
     */
    public function save_file($contents, $dir_and_name) {
        $dir = $dir_and_name[0];
        $name = $dir_and_name[1];

        $output_path = $this->output_base . $dir;
        if (!file_exists($output_path)) {
            mkdir($output_path, 0777, true);
        }

        $output_path = $this->output_base . $dir . '/' . $name;
        $test = file_put_contents($output_path, $contents);
        if ($test !== FALSE) {
            return 1;
        } else {
            print ("Unable to write to " . $output_path . "\n");
            return 0;
        }
    }

    /**
     * Given a URL what is the path and filename we want to save this as
     * locally. This is useful for '/my-blog-post/' links which will become
     * '/my-blog-post/index.html' on the local disk.
     *
     * Returns an array containing first the directory then the filename.
     */
    public function generate_local_name($url) {
        $comps = parse_url($url);
        $path   = $comps['path'];
        $dir    = $path;
        $name   = 'index.html';

        if (substr($path, -1) != '/') {
            $dir    = dirname($path) . '/';
            $name   = basename($path);
        }

        return array($dir, $name);
    }

    /**
     * Given a relative URL what is the path and filename we want to save this
     * as locally. This is intended to be used to fetch CSS resources, so the
     * supplied $from parameter is the point at which relative URLs are
     * handled.
     *
     * Returns an array containing first the directory then the filename.
     */
    public function generate_local_name_rel($url, $from) {

        // if URL is full URL, just return
        if (preg_match('/^https?:/i', $url)) {
            return $this->generate_local_name($url);
        }
        if (preg_match('#^//[^/]#', $url)) {
            return $this->generate_local_name('http:' . $url);
        }
        if (preg_match('#^/[^/]#', $url)) {
            return $this->generate_local_name($this->blogurl . $url);
        }

        // Otherwise it's relative directory from the location of $from...
        $comps  = parse_url($from);
        $path   = $comps['path'];

        // Join path from $from (exc css filename) with $url
        $last_slash_pos = strrpos($path, '/') + 1;
        $dir            = substr($path, 0, $last_slash_pos) . $url;

        $dir = preg_replace('#(.*?)(\.\/)*(.*?)#', "$1$3", $dir);

        // Split return path into dir path and resource name
        $last_slash_pos = strrpos($dir, '/') + 1;
        return array(substr($dir, 0, $last_slash_pos), substr($dir, $last_slash_pos));
    }
}
