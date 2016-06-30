<?php

/**
 * @file
 *
 */

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\typed_widget\Form\TypedElementBuilder;
use Drupal\Core\TypedData\DataDefinition;

/**
 * 
 * @group typed_widget
 */
class ListElementTest extends TypedElementTestBase {

  /**
   * Test a list of strings.
   *
   * @covers \Drupal\typed_widget\TypedElementBuilder::getListElement
   */
  public function testGetListElement() {
    $expected = [
      [
        '#type' => 'textfield',
        '#title' => 'Text',
        '#description' => '',
      ]
    ];

    $stringDefinition = DataDefinition::create('string');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Text');
    $listDefinition = new ListDataDefinition([], $stringDefinition);

    $typedDataManager = $this->getTypedDataMock($listDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('list');
    $this->assertEquals($expected, $element);
  }
}