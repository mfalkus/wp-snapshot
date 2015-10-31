<?php
include_once('lib/UrlHandler.php');

define('FAKE_CONTENTS',     'testing');
define('TEST_WP_URL',       'http://test-it.com');
define('TEST_OUTPUT_DIR',   './tests/tmp-output');

class SnapShotTest extends PHPUnit_Framework_TestCase {

    public $uh;
    function SnapShotTest() {
        $this->uh = new \wpsnapshot\UrlHandler(TEST_WP_URL, TEST_OUTPUT_DIR);
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
        $test = '/web-root/my-blog-post/';
        $out_test = $test . 'index.html';
        $dir_and_name = $this->uh->generate_local_name($test);

        // Write out a file
        $ok = $this->uh->save_file('FAKE_CONTENTS', $dir_and_name);

        // FALSE would be a failure
        $this->assertTrue($ok !== FALSE);

        // Is the content the same? Read back file and check
        $actual_file = file_get_contents(TEST_OUTPUT_DIR . $out_test);
        $this->assertEquals($actual_file, 'FAKE_CONTENTS');


        $test = '/web-root/images/photo.jpg';
        $out_test =  $test;
        $dir_and_name = $this->uh->generate_local_name($test);

        // Write out a file
        $ok = $this->uh->save_file('FAKE_CONTENTS', $dir_and_name);

        // FALSE would be a failure
        $this->assertTrue($ok !== FALSE);

        // Is the content the same? Read back file and check
        $actual_file = file_get_contents(TEST_OUTPUT_DIR . $out_test);
        $this->assertEquals($actual_file, 'FAKE_CONTENTS');



        $test = '/web-root/images/../photo-in-root.jpg';
        $out_test =  $test;
        $dir_and_name = $this->uh->generate_local_name($test);

        // Write out a file
        $ok = $this->uh->save_file('FAKE_CONTENTS', $dir_and_name);

        // FALSE would be a failure
        $this->assertTrue($ok !== FALSE);

        // Is the content the same? Read back file and check
        $actual_file = file_get_contents(TEST_OUTPUT_DIR . $out_test);
        $this->assertEquals($actual_file, 'FAKE_CONTENTS');


        $test = '/web-root/images/././././photo-not-in-root.jpg';
        $out_test =  $test;
        $dir_and_name = $this->uh->generate_local_name($test);

        // Write out a file
        $ok = $this->uh->save_file('FAKE_CONTENTS', $dir_and_name);

        // FALSE would be a failure
        $this->assertTrue($ok !== FALSE);

        // Is the content the same? Read back file and check
        $actual_file = file_get_contents(TEST_OUTPUT_DIR . $out_test);
        $this->assertEquals($actual_file, 'FAKE_CONTENTS');
    }
}

