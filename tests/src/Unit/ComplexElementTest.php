<?php

/**
 * @file
 *
 */

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\typed_widget\Form\TypedElementBuilder;

/**
 * 
 * @group typed_widget
 */
class ComplexElementTest extends TypedElementTestBase {

  /**
   * Test a map data type with a primitive data type as a child.
   *
   * @covers \Drupal\typed_widget\TypedElementBuilder::getComplexElement
   */
  public function testGetComplexElement() {
    $expected = [
      '#type' => 'container',
      '#tree' => TRUE,
      'text' => [
        '#type' => 'textfield',
        '#title' => 'Text',
        '#description' => '',
      ],
    ];
    
    $stringDefinition = DataDefinition::create('string');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Text');
    
    $mapDefinition = MapDataDefinition::create('map');
    $mapDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\Map')
      ->setLabel('Map')
      ->setPropertyDefinition('text', $stringDefinition);

    $typedDataManager = $this->getTypedDataMock($mapDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('map');
    $this->assertEquals($expected, $element);
    $this->assertEquals($expected['text'], $elementBuilder->getElementFor('map', 'text'));
  }
}