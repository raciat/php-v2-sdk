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
            CURLOPT_SSL_VERIFYPEER      => false,
            CURLOPT_SSL_VERIFYHOST      => false,
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
