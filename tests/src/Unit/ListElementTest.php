<?php

/**
 * @file
 * Contains ListElementTest
 */

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Test building lists of elements.
 *
 * @group typed_widget
 */
class ListElementTest extends TypedElementTestBase {

  /**
   * Assert that size cannot be less than zero.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLessThanZero() {
    $booleanDefinition = DataDefinition::create('boolean');
    $booleanDefinition->setLabel($this->getRandomGenerator()->name());
    $booleanDefinition->setClass('\Drupal\Core\TypedData\Plugin\DataType\BooleanData');
    $typedDataManager = $this->getTypedDataMock($booleanDefinition);

    // Set the container
    $this->setContainer($typedDataManager);

    $listDefinition = ListDataDefinition::create('boolean');

    $element_builder = $this->getElementBuilder($typedDataManager);

    $element_builder->getElementsForListDefinition($listDefinition, -1);
  }

  /**
   * Assert that a container element is returned with one child element.
   */
  public function testWithOneElement() {
    $expected = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => $this->getRandomGenerator()->sentences(5),
      0 => [
        '#type' => 'checkbox',
        '#title' => $this->getRandomGenerator()->name(),
        '#description' => '',
      ],
    ];

    $booleanDefinition = DataDefinition::create('boolean');
    $booleanDefinition->setLabel($expected[0]['#title']);
    $booleanDefinition->setClass('\Drupal\Core\TypedData\Plugin\DataType\BooleanData');
    $typedDataManager = $this->getTypedDataMock($booleanDefinition);

    // Set the container
    $this->setContainer($typedDataManager);

    $listDefinition = ListDataDefinition::create('boolean');
    $listDefinition->setLabel($expected['#title']);
    $listDefinition->setDescription($expected['#description']);

    $element_builder = $this->getElementBuilder($typedDataManager);
    $element = $element_builder->getElementForDefinition($listDefinition);

    $this->assertArrayEquals($expected, $element);
  }
}
