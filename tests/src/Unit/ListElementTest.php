<?php

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\typed_widget\Form\TypedElementBuilder;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Assert that a list item is rendered into a form element.
 *
 * @group typed_widget
 */
class ListElementTest extends TypedElementTestBase {

  /**
   * Test a list of strings.
   */
  public function testGetListElement() {
    $expected = [
      '#type' => 'container',
      '#tree' => TRUE,
      [
        '#type' => 'textfield',
        '#title' => 'Text',
        '#description' => '',
      ],
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
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('list');
    $this->assertArrayEquals($expected, $element);
  }

}
