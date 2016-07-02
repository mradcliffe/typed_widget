<?php
/**
 * @file
 * Contains \Drupal\typed_widget\Form\TypedElementBuilder.
 */

namespace Drupal\typed_widget\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
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
 * @endcode
 */
class TypedElementBuilder {

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
  public function __construct(TypedDataManagerInterface $typedDataManager, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory, ModuleHandlerInterface $moduleHandler) {
    $this->typedDataManager = $typedDataManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('typed_widget');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get the method on this class for the appropriate typed data element builder
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   * @return string
   *
   * @todo figure out approach to make this extendable.
   */
  private function getMethod(DataDefinitionInterface $definition) {
    if ($definition instanceof EntityDataDefinition) {
      return 'getEntityElement';
    }
    else if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
      return 'getComplexElement';
    }
    else if ($definition instanceof BaseFieldDefinition) {
      return 'getBaseFieldElement';
    }
    else if ($definition instanceof FieldItemDataDefinition) {
      return 'getFieldElement';
    }
    else if ($definition instanceof ListDataDefinitionInterface) {
      return 'getListElement';
    }
    else {
      return 'getPrimitiveElement';
    }
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
    try {
      $definition = $this->typedDataManager->createDataDefinition($plugin_id);

      $method = $this->getMethod($definition);
      $element = $this->{$method}($definition, $property_name);
    }
    catch (PluginNotFoundException $e) {
      throw $e;
    }
    return $element;
  }

  /**
   * Get a single element from a data definition for a primitive type.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   * @param string $property_name
   *   Not used.
   * @return array
   */
  public function getPrimitiveElement(DataDefinitionInterface $definition, $property_name = '') {
    // Get the element for the definition.
    $element_type = PrimitiveElementBuilder::getType($definition);
    $element = [
      '#type' => $element_type,
      '#title' => $definition->getLabel(),
      '#description' => $definition->getDescription() ? $definition->getDescription() : '',
    ];
    $element += PrimitiveElementBuilder::getProperties($element_type, $definition);
    $element += $this->getAdditionalProperties($element_type, $definition);
    return $element;
  }

  /**
   * Get the form element mapped to a complex data type.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface $parent_definition
   *   The complex data definition to render.
   * @param string $property_name
   *   (Optional) The property name to fetch from the complex data type.
   * @return array
   */
  public function getComplexElement(ComplexDataDefinitionInterface $parent_definition, $property_name = '') {
    if ($property_name) {
      /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
      $definition = $parent_definition->getPropertyDefinition($property_name);
      $method = $this->getMethod($definition);
      return $this->{$method}($definition);
    }

    // Create a container element for the complex data type.
    $element = $this->getParentContainer($parent_definition);

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    foreach ($parent_definition->getPropertyDefinitions() as $name => $definition) {
      if (!$definition->isComputed()) {
        $method = $this->getMethod($definition);
        $element[$name] = $this->{$method}($definition);
      }
    }

    return $element;
  }

  /**
   * Get an element for a field definition.
   *
   * @param \Drupal\Core\Field\TypedData\FieldItemDataDefinition $field_definition
   * @param string $property_name
   * @return array
   *
   * @todo write a unit test for this but this requires a test field item that
   *   does not suck because all of the field items depend on \t(). FML.
   */
  protected function getFieldElement(FieldItemDataDefinition $field_definition, $property_name = '') {
    if ($property_name) {
      /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
      $definition = $field_definition->getPropertyDefinition($property_name);
      $method = $this->getMethod($definition);
      return $this->{$method}($definition);
    }

    // Create a container element for the complex data type.
    $element = $this->getParentContainer($field_definition);

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    foreach ($field_definition->getPropertyDefinitions() as $name => $definition) {
      if (!$definition->isComputed()) {
        $method = $this->getMethod($definition);
        $element[$name] = $this->{$method}($definition);
      }
    }

    return $element;
  }

  /**
   * @param \Drupal\Core\Field\BaseFieldDefinition $definition
   * @param string $property_name
   * @return array
   */
  function getBaseFieldElement(BaseFieldDefinition $definition, $property_name = '') {
    $element = $this->getParentContainer($definition, 'fieldgroup');
    $method = $this->getMethod($definition->getItemDefinition());
    $element[0] = $this->{$method}($definition->getItemDefinition());
    return $element;
  }

  /**
   * Get an element for an entity type.
   *
   * @param \Drupal\Core\Entity\TypedData\EntityDataDefinition $entity_definition
   *   The entity data definition
   * @param $property_name
   *   (Optional) an optional property name to restrict to
   * @return array
   */
  function getEntityElement(EntityDataDefinition $entity_definition, $property_name = '') {

    $entity_type = $entity_definition->getEntityTypeId();
    $form_state = new FormState();

    $form = $this->entityTypeManager->getFormObject($entity_type, 'default');

    $form->setEntity($this->entityTypeManager->getStorage($entity_type)->create([]));
    $element = $form->buildForm([], $form_state);

    // Remove actions
    unset($element['actions']);

    if ($property_name) {
      return isset($element[$property_name]) ? $element[$property_name] : [];
    }

    return $element;
  }

  /**
   * Get an element container for a complex data definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   * @param string $type
   * @return array
   */
  protected function getParentContainer(DataDefinitionInterface $definition, $type = 'container') {
    $element = [
      '#type' => $type,
      '#tree' => TRUE,
    ];

    if ($type === 'fieldgroup') {
      $element['#title'] = $definition->getLabel();
      $element['#description'] = $definition->getDescription() ? $definition->getDescription() : '';
    }

    return $element;
  }

  /**
   * Get an element container for a list of items.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $definition
   * @param string $property_name
   * @return array
   *
   * @todo implement an add_more functionality.
   */
  public function getListElement(ListDataDefinitionInterface $definition, $property_name = '') {
    $element = [];

    $property_definition = $definition->getItemDefinition();
    $method = $this->getMethod($property_definition);
    $element[] = $this->{$method}($property_definition);

    return $element;
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
