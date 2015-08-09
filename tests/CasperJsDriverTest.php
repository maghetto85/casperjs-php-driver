<?php

namespace CasperJs\Driver;

/**
 * @author jacopo.nardiello
 */
class CasperJsDriverTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->driver = new CasperJsDriver();
    }

    public function testDriverWillLoadSimplePage()
    {
        $fixturePath = 'file://' . __DIR__ . '/fixtures/simpleHtml.html';
        $output = $this->driver->start($fixturePath)
                               ->run();

        $this->assertInstanceOf('\\CasperJs\\Driver\\Output', $output);
        $this->assertContains('Pizza with ketchup', $output->getHtml());
    }


    public function testDriverShouldUseProxy()
    {
        $driver = $this->getMockBuilder('CasperJs\Driver\CasperJsDriver')
                       ->setMethods(['addOption'])
                       ->getMock();
        $driver->expects($this->atLeastOnce())
               ->method('addOption');

        $output = $driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
                         ->useProxy('1.1.1.1')
                         ->run();
    }

    public function testBrowserInteractionIsBuiltProperly()
    {
        $expected = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});

casper.userAgent('AmericanPizzaiolo');
casper.page.customHeaders = {
    'Accept-Language': 'en-US',
    'Some-Header': 'Foo-bar'
};
casper.then(function() {
    casper.evaluate(function() {
        make me a pizza
    });
});
casper.then(function () {
    this.viewport(1024, 768);
});
casper.waitForSelector(
    '.selector',
    function () {
        this.echo('found selector \".selector\"');
    },
    function () {
        this.echo('" . Output::TAG_TIMEOUT . " $(.selector) not found after 30000 ms');
    },
    30000
);
casper.wait(
    10000,
    function () {
        this.echo('" . Output::TAG_TIMEOUT . " after waiting 10000 ms');
    }
);
casper.then(function() {
    this.click('.selector');
});";

        $this->driver->setUserAgent('AmericanPizzaiolo')
                     ->setHeaders([
                         'Accept-Language' => ['en-US'],
                         'Some-Header' => 'Foo-bar',
                     ])
                     ->evaluate('make me a pizza')
                     ->setViewPort(1024, 768)
                     ->waitForSelector('.selector', 30000)
                     ->wait(10000)
                     ->click('.selector');

        $this->assertEquals($expected, $this->driver->getScript());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage [TIMEOUT] $(.some-non-existent-selector) not found after 100 ms
     */
    public function testDriverShouldThrowExceptionWhenTimingOut()
    {
        $output = $this->driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
                               ->waitForSelector('.some-non-existent-selector', 100)
                               ->run();
    }
}
