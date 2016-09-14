<?php

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\typed_widget\Form\TypedElementBuilder;

/**
 * Assert that complex data types are rendered correctly.
 *
 * @group typed_widget
 */
class ComplexElementTest extends TypedElementTestBase {

  /**
   * Test a map data type with a primitive data type as a child.
   */
  public function testGetComplexElement() {
    $expected = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => 'Map',
      '#description' => '',
      'text' => [
        '#type' => 'textfield',
        '#title' => 'Text',
        '#description' => '',
      ],
      'number' => [
        '#type' => 'number',
        '#title' => 'Number',
        '#description' => '',
      ],
    ];

    $stringDefinition = DataDefinition::create('string');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Text');

    $intDefinition = DataDefinition::create('number');
    $intDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\IntegerData')
      ->setLabel('Number');

    $mapDefinition = MapDataDefinition::create('map');
    $mapDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\Map')
      ->setLabel('Map')
      ->setPropertyDefinition('text', $stringDefinition)
      ->setPropertyDefinition('number', $intDefinition);

    $typedDataManager = $this->getTypedDataMock($mapDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('map');
    $this->assertArrayEquals($expected, $element);
    $this->assertArrayEquals($expected['text'], $elementBuilder->getElementFor('map', 'text'));
  }

  /**
   * Test a map data type with primitive data type in ::getElementForData().
   */
  public function testGetElementForData() {
    $expected = [
      '#type' => 'fieldset',
      '#title' => 'Map',
      '#description' => '',
      '#tree' => TRUE,
      'text' => [
        '#type' => 'textfield',
        '#title' => 'Text',
        '#description' => '',
      ],
      'number' => [
        '#type' => 'number',
        '#title' => 'Number',
        '#description' => '',
      ],
    ];

    $stringDefinition = DataDefinition::create('string');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Text');

    $intDefinition = DataDefinition::create('number');
    $intDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\IntegerData')
      ->setLabel('Number');

    $mapDefinition = MapDataDefinition::create('map');
    $mapDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\Map')
      ->setLabel('Map')
      ->setPropertyDefinition('text', $stringDefinition)
      ->setPropertyDefinition('number', $intDefinition);

    $map = Map::createInstance($mapDefinition);
    $map->setValue([
      'text' => 'Test String',
      'number' => 5,
    ]);

    $typedDataManager = $this->getTypedDataMock($mapDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementForData($map);
    $this->assertArrayEquals($expected, $element);
  }

}
