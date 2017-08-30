<?php

namespace EntityXliff\Drupal\Tests\FieldHandlers;


use EntityXliff\Drupal\FieldHandlers\FormattedFieldHandler;

class FormattedFieldHandlerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that the formatted text field handler returns the "value" key on the
   * array returned by $wrapper->value();
   *
   * @test
   */
  public function getValueValue() {
    $expectedValue = 'value';
    $expectedFieldLabel = 'Field Label';

    $observerWrapper = $this->getMock('\EntityMetadataWrapper', array('value', 'info'));
    $observerWrapper->expects($this->once())
      ->method('value')
      ->willReturn(array('value' => $expectedValue));
    $observerWrapper->expects($this->once())
      ->method('info')
      ->willReturn(array('label' => $expectedFieldLabel));

    $handler = new FormattedFieldHandler();
    $value = $handler->getValue($observerWrapper);
    $this->assertEquals($expectedValue, $value['value']['#text']);
    $this->assertEquals($expectedFieldLabel . ' (value)', $value['value']['#label']);
    $this->assertNotTrue(isset($value['summary']));
  }

  /**
   * Tests that the formatted text field handler returns the "summary" key on the
   * array returned by $wrapper->value();
   *
   * @test
   */
  public function getValueSummary() {
    $expectedSummary = 'summary value';
    $expectedFieldLabel = 'Field Label';

    $observerWrapper = $this->getMock('\EntityMetadataWrapper', array('value', 'info'));
    $observerWrapper->expects($this->once())
      ->method('value')
      ->willReturn(array('summary' => $expectedSummary));
    $observerWrapper->expects($this->once())
      ->method('info')
      ->willReturn(array('label' => $expectedFieldLabel));

    $handler = new FormattedFieldHandler();
    $value = $handler->getValue($observerWrapper);
    $this->assertEquals($expectedSummary, $value['summary']['#text']);
    $this->assertEquals($expectedFieldLabel . ' (summary)', $value['summary']['#label']);
    $this->assertNotTrue(isset($value['value']));
  }

}
