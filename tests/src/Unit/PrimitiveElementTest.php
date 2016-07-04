<?php

namespace Drupal\Tests\typed_widget\Unit;


use Drupal\typed_widget\Form\TypedElementBuilder;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Tests that primitive data types can have elements.
 *
 * @group typed_widget
 */
class PrimitiveElementTest extends TypedElementTestBase {

  /**
   * Assert that a checkbox element is provided for a boolean.
   */
  public function testBoolean() {
    $expected = [
      '#type' => 'checkbox',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
    ];

    $booleanDefinition = DataDefinition::create('boolean');
    $booleanDefinition->setLabel($expected['#title']);
    $booleanDefinition->setClass('\Drupal\Core\TypedData\Plugin\DataType\BooleanData');
    $typedDataManager = $this->getTypedDataMock($booleanDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('boolean');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that a textfield element is provided for a string.
   */
  public function testString() {
    $expected = [
      '#type' => 'textfield',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => $this->getRandomGenerator()->sentences(10, TRUE),
      '#required' => TRUE,
    ];

    $stringDefinition = DataDefinition::create('string');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description'])
      ->setRequired(TRUE);
    $typedDataManager = $this->getTypedDataMock($stringDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('string');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that number widget used for integers.
   */
  public function testInteger() {
    $expected = [
      '#type' => 'number',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
      '#min' => 0,
      '#max' => 10,
    ];

    $integerDefinition = DataDefinition::create('integer');
    $integerDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\IntegerData')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description'])
      ->addConstraint('Range', ['min' => 0, 'max' => 10]);
    $constraints = [
      'Range' => ['min' => 0, 'max' => 10],
    ];
    $typedDataManager = $this->getTypedDataMock($integerDefinition, $constraints);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('integer');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that number element is used for float.
   */
  public function testFloat() {
    $expected = [
      '#type' => 'number',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
      '#min' => 0,
      '#max' => 10,
    ];

    $floatDefinition = DataDefinition::create('float');
    $floatDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\FloatData')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description'])
      ->addConstraint('Range', ['min' => 0, 'max' => 10]);
    $constraints = [
      'Range' => ['min' => 0, 'max' => 10],
    ];
    $typedDataManager = $this->getTypedDataMock($floatDefinition, $constraints);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('float');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that datetime element is returned for timestamp.
   */
  public function testTimestamp() {
    $expected = [
      '#type' => 'datetime',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
    ];

    $timeDefinition = DataDefinition::create('timestamp');
    $timeDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\Timestamp')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description']);
    $typedDataManager = $this->getTypedDataMock($timeDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('timestamp');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that datetime element is returned for datetimeiso8601.
   */
  public function testDatetimeIso8601() {
    $expected = [
      '#type' => 'datetime',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
    ];

    $timeDefinition = DataDefinition::create('datetime_iso8601');
    $timeDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description']);
    $typedDataManager = $this->getTypedDataMock($timeDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('datetime_iso8601');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that a number element is set appropriately for a timespan data type.
   *
   * The number element should have min and max values set.
   */
  public function testTimespan() {
    $expected = [
      '#type' => 'number',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
      '#min' => 0,
      '#max' => 86400,
    ];

    $durationDefinition = DataDefinition::create('timespan');
    $durationDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\TimeSpan')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description']);
    $typedDataManager = $this->getTypedDataMock($durationDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('timespan');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that a textfield element is provided for a duration_iso8601 type.
   */
  public function testDurationIso8601() {
    $expected = [
      '#type' => 'textfield',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
    ];

    $stringDefinition = DataDefinition::create('duration_iso8601');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\DurationIso8601')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description']);
    $typedDataManager = $this->getTypedDataMock($stringDefinition);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('duration_iso8601');
    $this->assertEquals($expected, $element);
  }

  /**
   * Assert that a select element has the AllowedValues constraint.
   */
  public function testAllowedValues() {
    $expected = [
      '#type' => 'select',
      '#title' => $this->getRandomGenerator()->name(),
      '#description' => '',
      '#options' => ['one' => 'One', 'two' => 'Two', 'three' => 'Three'],
    ];

    $stringDefinition = DataDefinition::create('string');
    $stringDefinition
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel($expected['#title'])
      ->setDescription($expected['#description'])
      ->addConstraint('Choice', ['choices' => $expected['#options']]);
    $typedDataManager = $this->getTypedDataMock($stringDefinition, ['Choice' => ['choices' => $expected['#options']]]);

    // Set the container.
    $this->setContainer($typedDataManager);

    $elementBuilder = new TypedElementBuilder(
      $typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $element = $elementBuilder->getElementFor('string');
    $this->assertEquals($expected, $element);
  }

}
