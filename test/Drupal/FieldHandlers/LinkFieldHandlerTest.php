<?php

namespace EntityXliff\Drupal\Tests\FieldHandlers;


use EntityXliff\Drupal\FieldHandlers\LinkFieldHandler;

class LinkFieldHandlerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that the link field handler returns the "title" key on the array
   * returned by $wrapper->value();
   *
   * @test
   */
  public function getValueTitle() {
    $expectedResponse = 'value';

    $observerWrapper = $this->getMock('\EntityMetadataWrapper', array('value'));
    $observerWrapper->expects($this->once())
      ->method('value')
      ->willReturn(array('title' => $expectedResponse));

    $handler = new LinkFieldHandler();
    $value = $handler->getValue($observerWrapper);
    $this->assertEquals($expectedResponse, $value['title']['#text']);
    $this->assertEquals('Title text', $value['title']['#label']);
  }

  /**
   * Tests that the link field handler returns the "url" key on the array
   * returned by $wrapper->value();
   * @test
   */
  public function getValueUrl() {
    $expectedResponse = 'http://example.com/en-us';

    $observerWrapper = $this->getMock('\EntityMetadataWrapper', array('value'));
    $observerWrapper->expects($this->once())
      ->method('value')
      ->willReturn(array('url' => $expectedResponse));

    $handler = new LinkFieldHandler();
    $value = $handler->getValue($observerWrapper);
    $this->assertEquals($expectedResponse, $value['url']['#text']);
    $this->assertEquals('Link URL', $value['url']['#label']);
  }

}
