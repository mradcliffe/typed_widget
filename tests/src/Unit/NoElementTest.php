<?php

/**
 * @file
 * Contains NoElementTest
 */

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\DataDefinition;

/**
 *
 * @group typed_widget
 */
class NoElementTest extends TypedElementTestBase {

  /**
   * Assert that computed data type is not rendered into a form element.
   */
  public function testComputed() {
    $definition = DataDefinition::create('string');
    $definition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setComputed(TRUE);

    $typedDataManager = $this->getTypedDataMock($definition);
    // Set the container
    $this->setContainer($typedDataManager);

    $elementBuilder = $this->getElementBuilder($typedDataManager);

    // Try with getElementForDefinition
    $element = $elementBuilder->getElementForDefinition($definition);
    $this->assertEmpty($element);

    // Try with getElementFor
    $element = $elementBuilder->getElementFor('string');
    $this->assertEmpty($element);
  }
  
  /**
   * Assert that read-only data type is not rendered into a form element.
   */
  public function testReadOnly() {
    $definition = DataDefinition::create('string');
    $definition
    ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
    ->setReadOnly(TRUE);
    
    $typedDataManager = $this->getTypedDataMock($definition);
    // Set the container
    $this->setContainer($typedDataManager);
    
    $elementBuilder = $this->getElementBuilder($typedDataManager);

    // Try with getElementForDefinition
    $element = $elementBuilder->getElementForDefinition($definition);
    $this->assertEmpty($element);

    // Try with getElementFor
    $element = $elementBuilder->getElementFor('string');
    $this->assertEmpty($element);
  }
}
