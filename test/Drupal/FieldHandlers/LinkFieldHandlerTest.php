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
  public function getValue() {
    $expectedResponse = 'value';

    $observerWrapper = $this->getMock('\EntityMetadataWrapper', array('value'));
    $observerWrapper->expects($this->once())
      ->method('value')
      ->willReturn(array('title' => $expectedResponse));

    $handler = new LinkFieldHandler();
    $this->assertEquals($expectedResponse, $handler->getValue($observerWrapper));
  }

}
