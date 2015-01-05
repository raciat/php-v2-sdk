<?php
namespace Ooyala\Tests;

use PHPUnit_Framework_TestCase;
use OoyalaHttpRequest;
use OoyalaRequestErrorException;

require_once '../vendor/autoload.php';

class OoyalaHttpRequestTest extends PHPUnit_Framework_TestCase
{
    public function testInitializationWithDefaultOptions()
    {
        $ooyalaHttpRequest = new OoyalaHttpRequest();

        $this->assertSame($ooyalaHttpRequest::$curlDefaultOptions, $ooyalaHttpRequest->curlOptions);
        $this->assertFalse($ooyalaHttpRequest->shouldFollowLocation);
        $this->assertNull($ooyalaHttpRequest->contentType);
    }

    /**
     * @see http://mx.php.net/manual/en/function.curl-setopt.php
     * Manual states that CURLOPT_SSL_VERIFYHOST should contain
     */
    public function testDefaultCurlOptions() {
        $ooyalaHttpRequest = new OoyalaHttpRequest();

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

        $ooyalaHttpRequest = new OoyalaHttpRequest(array(
            'curlOptions' => $customOptions
        ));

        $expected = array_replace($ooyalaHttpRequest::$curlDefaultOptions, $customOptions);
        $this->assertSame($expected, $ooyalaHttpRequest->curlOptions);
    }

    public function testCustomContentType()
    {
        $contentType = 'anyType';

        $ooyalaHttpRequest = new OoyalaHttpRequest(array(
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
     * This test is indirect as there is no way to access curl options from outside execute.
     */
    public function testOverwritingOptionsWhenExecute() {
        $contentType = 'anyType';
        $followLocation = true;

        $customOptions = array(
            'contentType' => $contentType,
            'curlOptions' => array(
                CURLOPT_DNS_CACHE_TIMEOUT   => 25
            ),
            'shouldFollowLocation' => $followLocation
        );

        $ooyalaHttpRequest = new OoyalaHttpRequest();

        try {
            $ooyalaHttpRequest->execute('get', 'http://127.0.0.1/invalid/location.json', $customOptions);
        } catch (OoyalaRequestErrorException $e) {
            // disregard connection errors, check curl options
            $expectedCurlOptions = $this->getExpectedCurlOptions($customOptions['curlOptions']);

            $this->assertSame($expectedCurlOptions, $ooyalaHttpRequest->curlOptions);
            $this->assertSame($followLocation, $ooyalaHttpRequest->shouldFollowLocation);
            $this->assertSame($contentType, $ooyalaHttpRequest->contentType);
        }
    }

    /**
     * This test shows the real-life use-case of execute options
     */
    public function testPassingPayloadAsOptionsToExecute() {
        $ooyalaHttpRequest = new OoyalaHttpRequest();

        $payload = array('payload' => 'any request body');
        try {
            $ooyalaHttpRequest->execute('get', 'http://127.0.0.1/invalid/location.json', $payload);
        } catch (OoyalaRequestErrorException $e) {
            // disregard connection errors, check curl options
            $this->assertSame(OoyalaHttpRequest::$curlDefaultOptions, $ooyalaHttpRequest->curlOptions);

            // In order to check payload, either curl_exec needs to be mocked or valid response needs to be returned
        }
    }

    /**
     * @expectedException OoyalaRequestErrorException
     */
    public function testExecuteWithError()
    {
        $ooyalaHttpRequest = new OoyalaHttpRequest();
        $ooyalaHttpRequest->execute('get', 'http://invalid');
    }

    /**
     * @expectedException OoyalaRequestErrorException
     */
    public function testExecuteWithResponseError()
    {
        $ooyalaHttpRequest = new OoyalaHttpRequest();
        $ooyalaHttpRequest->execute('get', 'http://127.0.0.1/invalid/location.json');
    }

    public function testWithOverridingOptions()
    {
        $this->markTestSkipped('It creates a real request that requires 127.0.0.1:80 to answer. Skipping.');

        $ooyalaHttpRequest = new OoyalaHttpRequest();
        $response = $ooyalaHttpRequest->execute('get', 'http://127.0.0.1', array(
            'payload' => 'payload',
            'contentType' => 'text/plain'));

        $this->assertEquals(200, $response['status']);
    }

    /**
     * @param array $customOpts
     * @return array
     */
    private function getExpectedCurlOptions(array $customOpts) {
        return array_replace(OoyalaHttpRequest::$curlDefaultOptions, $customOpts);
    }
}
