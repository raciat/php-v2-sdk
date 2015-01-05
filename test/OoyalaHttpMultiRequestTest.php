<?php
namespace Ooyala\Tests;

use PHPUnit_Framework_TestCase;
use OoyalaHttpMultiRequest;
use OoyalaRequestErrorException;

require_once '../vendor/autoload.php';

class OoyalaHttpMultiRequestTest extends PHPUnit_Framework_TestCase
{
    public function testInitializationWithDefaultOptions()
    {
        $ooyalaHttpRequest = new OoyalaHttpMultiRequest();

        $this->assertSame($ooyalaHttpRequest::$curlDefaultOptions, $ooyalaHttpRequest->curlOptions);
        $this->assertFalse($ooyalaHttpRequest->shouldFollowLocation);
        $this->assertNull($ooyalaHttpRequest->contentType);
    }

    /**
     * @see http://mx.php.net/manual/en/function.curl-setopt.php
     * Manual states that CURLOPT_SSL_VERIFYHOST should contain an integer but original library
     * uses boolean.
     */
    public function testDefaultCurlOptions() {
        $ooyalaHttpRequest = new OoyalaHttpMultiRequest();

        $expectedCurlDefaults = array(
            CURLOPT_SSL_VERIFYPEER      => false,
            CURLOPT_SSL_VERIFYHOST      => false,
            CURLOPT_DNS_CACHE_TIMEOUT   => 0,
        );

        $this->assertSame($expectedCurlDefaults, $ooyalaHttpRequest::$curlDefaultOptions);
        // Test that default option are properly set by constructor
        $this->assertSame($expectedCurlDefaults, $ooyalaHttpRequest->curlOptions);
    }

    public function testCustomCurlOptions() {
        $customOptions = array(
            CURLOPT_DNS_CACHE_TIMEOUT   => 25,
        );

        $ooyalaHttpRequest = new OoyalaHttpMultiRequest(array(
            'curlOptions' => $customOptions
        ));

        $expected = array_replace($ooyalaHttpRequest::$curlDefaultOptions, $customOptions);
        $this->assertSame($expected, $ooyalaHttpRequest->curlOptions);
    }

    public function testCustomContentType()
    {
        $contentType = 'anyType';

        $ooyalaHttpRequest = new OoyalaHttpMultiRequest(array(
            'contentType' => $contentType));

        $this->assertSame($contentType, $ooyalaHttpRequest->contentType);
    }

    /**
     * There is a mechanism to pass payload of request as options when executing.
     * Unfortunately, it uses the same mechanism that is used to initialize curl options.
     * In result, there is a possibility to overwrite curl options when using execute().
     * In theory, this should completely overwrite any options previously set
     * but previous implementation did not merge curl options properly.
     *
     * When you pass custom curl options via execute() third param, they will be set to curl_setopt
     * as they are passed, without merging with defaults.
     *
     * New implementation merges them correctly, which is tested here.
     *
     * This test is indirect as there is no way to access curl options from outside executeMulti.
     */
    public function testOverwritingOptionsWhenExecuteMulti() {
        $contentType = 'anyType';
        $followLocation = true;

        $customOptions = array(
            'contentType' => $contentType,
            'curlOptions' => array(
                CURLOPT_DNS_CACHE_TIMEOUT   => 25
            ),
            'shouldFollowLocation' => $followLocation
        );

        $ooyalaHttpRequest = new OoyalaHttpMultiRequest();
        $requests = array(
            array(
                'url' => 'http://127.0.0.1/invalid/location.json',
                'options' => $customOptions
            )
        );

        try {
            $ooyalaHttpRequest->executeMulti('get', $requests);
        } catch (OoyalaRequestErrorException $e) {
            // disregard connection errors, check curl options
            $expectedCurlOptions = $this->getExpectedCurlOptions($customOptions['curlOptions']);

            $this->assertSame($expectedCurlOptions, $ooyalaHttpRequest->curlOptions);
            $this->assertSame($followLocation, $ooyalaHttpRequest->shouldFollowLocation);
            $this->assertSame($contentType, $ooyalaHttpRequest->contentType);
        }
    }

    /**
     * @param array $customOpts
     * @return array
     */
    private function getExpectedCurlOptions(array $customOpts) {
        return array_replace(OoyalaHttpMultiRequest::$curlDefaultOptions, $customOpts);
    }
}
