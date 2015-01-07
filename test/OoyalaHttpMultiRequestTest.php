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
            CURLOPT_SSL_VERIFYPEER      => false,
            CURLOPT_SSL_VERIFYHOST      => false,
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
}
