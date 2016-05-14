<?php
/**
 * @file
 * Contains \Drupal\typed_widget\Form\TypedElementBuilder.
 */

namespace Drupal\typed_widget\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * The typed_widget.builder service provides methods to create form elements
 * from Typed Data data types defined in Drupal.
 *
 * Example usage:
 * @code
 * // Get form element required for a primitive data type.
 * $formBuilder = \Drupal::service('typed_widget.element_builder');
 * $form['date'] = $formBuilder->getElementFor('datetime_iso8601');
 * $form['string'] = $formBuilder->getElementForDefinition(DataDefinition::create('string'));
 * @endcode
 */
class TypedElementBuilder {

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * @var \Drupal\Core\Logger\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface
   *   The logger channel factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  public function __construct(TypedDataManagerInterface $typedDataManager, LoggerChannelFactoryInterface $loggerFactory, ModuleHandlerInterface $moduleHandler) {
    $this->typedDataManager = $typedDataManager;
    $this->logger = $loggerFactory->get('typed_widget');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Create a render element for the given data type plugin.
   *
   * @param string $plugin_id
   *   The data type plugin id.
   * @param string $property_name
   *   (Optional) A property name of the data type to return.
   * @return array
   *   A render array.
   * @throws PluginNotFoundException
   */
  public function getElementFor($plugin_id, $property_name = '') {
    $element = [];

    try {
      $definition = $this->typedDataManager->createDataDefinition($plugin_id);

      if ($definition->isComputed() || $definition->isReadOnly()) {
        return $element;
      }

      if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
        if (empty($property_name)) {
          // Get all elements for the definition.
          $element = $this->getElementsForDefinition($definition);
        }
        else {
          // Get the element for the property of the definition.
          $element = $this->getElementForDefinition($definition->getPropertyDefinition($property_name));
        }
      }
      elseif ($definition->isList()) {
        // @todo This may need to be moved above.
        $element = $this->getElementsForListDefinition($definition);
      }
      else {
        // Get the element for the definition.
        $element_type = PrimitiveElementBuilder::getType($definition);
        $element = [
          '#type' => $element_type,
          '#title' => $definition->getLabel(),
          '#description' => $definition->getDescription() ? $definition->getDescription() : '',
        ];
        $element += PrimitiveElementBuilder::getProperties($element_type, $definition);
        $element += $this->getAdditionalProperties($element_type, $definition);
      }
    }
    catch (PluginNotFoundException $e) {
      throw $e;
    }

    return $element;
  }

  /**
   * Get a nested form element for a complex data definition with all elements
   * being required properties.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionBase $parent_definition
   *   The data definition to populate the element for.
   * @return array
   *   A nested form element.
   */
  public function getElementsForDefinition(ComplexDataDefinitionBase $parent_definition) {
    $element = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $parent_definition->getLabel(),
      '#description' => $parent_definition->getDescription() ? $parent_definition->getDescription() : '',
    ];

    // Each property definition needs to be analysed to find out the best
    // element match.
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    foreach ($parent_definition->getPropertyDefinitions() as $name => $definition) {
      // Read-only properties should not be added to the element.
      if (!$definition->isReadOnly()) {
         if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
           // A complex data definition should recurse.
           $element[$name] = $this->getElementsForDefinition($definition);
        }
        elseif ($definition->isList()) {
          // Get the list of elements.
          $element[$name] = $this->getElementsForListDefinition($definition);
        }
        else {
          // Get the element for the definition.
          $element_type = PrimitiveElementBuilder::getType($definition);
          $element = [
            '#type' => $element_type,
            '#title' => $definition->getLabel(),
            '#description' => $definition->getDescription() ? $definition->getDescription() : '',
          ];
          $element += PrimitiveElementBuilder::getProperties($element_type, $definition);
          $element += $this->getAdditionalProperties($element_type, $definition);
        }
      }
    }

    return $element;
  }

  /**
   * Get the form element for a data definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The complex data definition.
   * @return array The form element.
   *   The form element.
   */
  public function getElementForDefinition(DataDefinitionInterface $definition) {
    $element = [];

    if ($definition->isComputed() || $definition->isReadOnly()) {
      return $element;
    }

    if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
      $element = $this->getElementsForDefinition($definition);
    }
    elseif ($definition->isList()) {
      $element = $this->getElementsForListDefinition($definition);
    }
    else {
      // Get the element for the definition.
      $element_type = PrimitiveElementBuilder::getType($definition);
      $element = [
        '#type' => $element_type,
        '#title' => $definition->getLabel(),
        '#description' => $definition->getDescription() ? $definition->getDescription() : '',
      ];
      $element += PrimitiveElementBuilder::getProperties($element_type, $definition);
      $element += $this->getAdditionalProperties($element_type, $definition);
    }

    return $element;
  }

  /**
   * Get the elements for a list definition.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $list_definition
   *   A list data definition
   * @param integer $size
   *   How many child elements to create. Default to 1 element.
   * @return array
   *   The nested form element.
   */
  public function getElementsForListDefinition(ListDataDefinitionInterface $list_definition, $size = 1) {
    $element = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $list_definition->getLabel(),
      '#description' => $list_definition->getDescription() ? $list_definition->getDescription() : '',
    ];

    $definition = $list_definition->getItemDefinition();

    if ($size < 0) {
      throw new \InvalidArgumentException('Element size must be 0 or greater.');
    }

    for ($i = 0; $i < $size; $i++) {
      if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
        $element[] = $this->getElementsForDefinition($definition);
      }
      elseif ($definition->isList()) {
        $element[] = $this->getElementsForListDefinition($definition);
      }
      elseif (!$definition->isComputed()) {
        $element_type = PrimitiveElementBuilder::getType($definition);
        $element[] = [
          '#type' => $element_type,
          '#title' => $definition->getLabel(),
          '#description' => $definition->getDescription() ? $definition->getDescription() : '',
        ]
          + PrimitiveElementBuilder::getProperties($element_type, $definition)
          + $this->getAdditionalProperties($element_type, $definition);
      }
    }

    return $element;
  }

  /**
   * Get the element type to use for a definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   * @return string
   *   The element machine name to use.
   */
  protected function getElementTypeFromDefinition(DataDefinitionInterface $definition) {
    $type = 'textfield';

    $implementations = class_implements($definition->getClass());

    if ($definition->getDataType() === 'boolean') {
      $type = 'checkbox';
    }
    elseif (in_array('Drupal\Core\TypedData\Type\DateTimeInterface', $implementations)) {
      $type = 'datetime';
    }
    elseif (in_array('Drupal\Core\TypedData\Type\IntegerInterface', $implementations) ||
        in_array('Drupal\Core\TypedData\Type\FloatInterface', $implementations)) {
      $type = 'number';
    }
    elseif ($definition->getConstraint('AllowedValues') || $definition->getConstraint('Choice')) {
      $type = 'select';
    }

    // Allow a module to alter the type.
    $this->moduleHandler->alter('typed_element_type', $type, $definition);

    return $type;
  }

  /**
   * Get any additional form properties to merge into the element for a given
   * form element type.
   *
   * @param string $type
   *   The form element type property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition
   * @param string $parent_type
   *   (Optional) The parent data type.
   * @return array
   *   An array of properties to merge into the form element.
   */
  protected function getAdditionalProperties($type, DataDefinitionInterface $definition, $parent_type = '') {
    $properties = [];

    if ($definition->isRequired()) {
      $properties['#required'] = TRUE;
    }

    if ($definition->isReadOnly()) {
      $properties['#disabled'] = TRUE;
    }

    return $properties;
  }
}
