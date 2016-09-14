<?php

namespace Drupal\Tests\typed_widget\Unit;


use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\typed_widget\Form\TypedElementBuilder;

/**
 * Test helper methods on TypedElementBuilder.
 *
 * @group typed_widget
 */
class TypedElementBuilderTest extends TypedElementTestBase {

  protected $definition;
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $requiredDefinition = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Required Property');
    $readOnlyDefinition = DataDefinition::create('string')
      ->setReadOnly(TRUE)
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Read-only Property');
    $defaultDefinition = DataDefinition::create('string')
      ->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData')
      ->setLabel('Default Property');

    $this->definition = MapDataDefinition::create('map');
    $this->definition->setLabel('Map');
    $this->definition->setPropertyDefinition('required', $requiredDefinition);
    $this->definition->setPropertyDefinition('readonly', $readOnlyDefinition);
    $this->definition->setPropertyDefinition('default', $defaultDefinition);

    $this->typedDataManager = $this->getTypedDataMock($this->definition);
    $this->setContainer($this->typedDataManager);
  }

  /**
   * Assert that non-required properies are not in the returned element.
   */
  public function testHideNonRequiredProperties() {
    $expected = [
      '#type' => 'fieldset',
      '#title' => 'Map',
      '#description' => '',
      '#tree' => TRUE,
      'required' => [
        '#type' => 'textfield',
        '#title' => 'Required Property',
        '#description' => '',
        '#required' => TRUE,
      ],
    ];

    $elementBuilder = new TypedElementBuilder(
      $this->typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $this->assertTrue($elementBuilder->getNonRequiredProperties());

    $elementBuilder->setNonRequiredProperties(FALSE);

    $element = $elementBuilder->getElementFor('map');

    $this->assertArrayEquals($expected, $element);
  }

  /**
   * Assert that read-only properties are built into the element.
   */
  public function testShowReadOnlyProperties() {
    $expected = [
      '#type' => 'fieldset',
      '#title' => 'Map',
      '#description' => '',
      '#tree' => TRUE,
      'required' => [
        '#type' => 'textfield',
        '#title' => 'Required Property',
        '#description' => '',
        '#required' => TRUE,
      ],
      'default' => [
        '#type' => 'textfield',
        '#title' => 'Default Property',
        '#description' => '',
      ],
      'readonly' => [
        '#type' => 'textfield',
        '#title' => 'Read-only Property',
        '#description' => '',
        '#disabled' => TRUE,
      ],
    ];

    $elementBuilder = new TypedElementBuilder(
      $this->typedDataManager,
      $this->getEntityTypeManagerMock(),
      $this->getLogger(),
      $this->getModuleHandlerMock()
    );

    $this->assertFalse($elementBuilder->getDisplayReadOnly());

    $elementBuilder->setDisplayReadOnly(TRUE);

    $element = $elementBuilder->getElementFor('map');

    $this->assertArrayEquals($expected, $element);
  }

  /**
   * Get prophesized mock for Typed Data Manager to create data definitions.
   *
   * It is not necessary to mock the createInstance methods at this time, but
   * maybe in the future?
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The definition to create.
   * @param array $constraints
   *   An array of constraint definitions keyed by constraint name.
   *
   * @return \Drupal\Core\TypedData\TypedDataManagerInterface
   *   Typed Data Manager.
   */
  protected function getTypedDataMock(DataDefinitionInterface $definition, array $constraints = []) {
    $typedDataProphecy = $this->prophesize('\Drupal\Core\TypedData\TypedDataManagerInterface');
    $typedDataProphecy->createDataDefinition($definition->getDataType())->willReturn($definition);
    $typedDataProphecy->getDefaultConstraints($definition)->willReturn($constraints);
    $typedDataProphecy->getDefinition($definition->getDataType())->willReturn($definition);
    $typedDataProphecy->getDefinitions()->willReturn([$definition->getDataType() => $definition]);

    if ($definition instanceof ComplexDataDefinitionInterface) {
      /* $definition \Drupal\Core\TypedData\ComplexDataDefinitionInterface $definition */
      foreach ($definition->getPropertyDefinitions() as $name => $child_definition) {
        $typedDataProphecy->createDataDefinition($child_definition->getDataType())
          ->willReturn($child_definition);
        $typedDataProphecy->getDefaultConstraints($child_definition)
          ->willReturn([]);
        $typedDataProphecy->getDefinition($child_definition->getDataType())
          ->willReturn($child_definition);
      }
    }

    return $typedDataProphecy->reveal();
  }

}
