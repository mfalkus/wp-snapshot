<?php
include_once('lib/UrlHandler.php');

class SnapShotTest extends PHPUnit_Framework_TestCase {

    public $uh;
    function SnapShotTest() {
        $this->uh = new \wpsnapshot\UrlHandler('http://test-it.com', './test-tmp');
    }

    function test_generate_local_name() {
        $path = '/my-blog-post/';
        $test = $this->uh->blogurl . $path;
        $result = $this->uh->generate_local_name($test);

        $this->assertEquals($path, $result[0]);
        $this->assertEquals('index.html', $result[1]);

        $path = '/my-blog-post/and-file.abc';
        $test = $this->uh->blogurl . $path;
        $result = $this->uh->generate_local_name($test);

        $this->assertEquals('/my-blog-post/', $result[0]);
        $this->assertEquals('and-file.abc', $result[1]);

        // Test without URL, just absolute link
        $test = '/from-web-root/my-blog-post/and-file.abc';
        $result = $this->uh->generate_local_name($test);

        $this->assertEquals('/from-web-root/my-blog-post/', $result[0]);
        $this->assertEquals('and-file.abc', $result[1]);
    }

    function test_generate_local_name_rel() {
        $path = '/wp-content/themes/example/';
        $css_url = $this->uh->blogurl . $path . 'style.css';

        // Example CSS urls from $css_url
        $test = './logo.png';
        $result = $this->uh->generate_local_name_rel($test, $css_url);

        $this->assertEquals($path, $result[0]);
        $this->assertEquals('logo.png', $result[1]);

        // Multiple same directory reference
        $test = './././././logo.png';
        $result = $this->uh->generate_local_name_rel($test, $css_url);

        $this->assertEquals($path, $result[0]);
        $this->assertEquals('logo.png', $result[1]);
    }

    function test_save_file() {
        $this->assertTrue(true);
    }
}

