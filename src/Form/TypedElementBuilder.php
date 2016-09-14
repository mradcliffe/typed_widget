<?php

namespace Drupal\typed_widget\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * The typed_widget.builder service creates form elements from data types.
 *
 * Example usage:
 *
 * @code
 * // Get form element required for a primitive data type.
 * $formBuilder = \Drupal::service('typed_widget.element_builder');
 * $form['date'] = $formBuilder->getElementFor('datetime_iso8601');
 *
 * // Get form element required for an entity type.
 * $form = $formBuilder->getElementFor('entity:user');
 * unset($form['#process'][0]);
 * $mailElement = $formBuilder->getElementFor('entity:user', 'mail');
 *
 * // Get form elements required for an Article node.
 * $form = $formBuilder->getElementFor('entity:node', NULL, ['type' => 'article']);
 * unset($form['#process][0]);
 *
 * // Get form element for the Favorite Color field attached to the Person node.
 * $element = $formBuilder->getElementFor('entity:node', 'field_favorite_color', ['type' => 'person']);
 *
 * // Get form element required for field property definitions without the
 * // context of an entity or form display.
 * $form['phone'] = $formBuilder->getElementFor('field_item:telephone');
 * @endcode
 */
class TypedElementBuilder {

  /**
   * Typed Data Manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   *   The typed data manager.
   */
  protected $typedDataManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * Logger Channel for Typed Widget.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger channel object.
   */
  protected $logger;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected $moduleHandler;

  /**
   * Toggle display non-required properties.
   *
   * @var bool
   */
  protected $nonRequiredProperties;

  /**
   * Toggle display read-only properties.
   *
   * @var bool
   */
  protected $readOnlyProperties;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(TypedDataManagerInterface $typedDataManager, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory, ModuleHandlerInterface $moduleHandler) {
    $this->typedDataManager = $typedDataManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('typed_widget');
    $this->moduleHandler = $moduleHandler;

    // Display non-required by default, but hide read-only properties.
    $this->nonRequiredProperties = TRUE;
    $this->readOnlyProperties = FALSE;
  }

  /**
   * Get the method for the appropriate typed data element builder.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The Typed Data definition.
   *
   * @return string
   *   A method name.
   *
   * @todo figure out approach to make this extendable.
   */
  private function getMethod(DataDefinitionInterface $definition) {
    if ($definition instanceof EntityDataDefinition) {
      return 'getEntityElement';
    }
    elseif (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
      return 'getComplexElement';
    }
    elseif ($definition instanceof BaseFieldDefinition) {
      return 'getBaseFieldElement';
    }
    elseif ($definition instanceof FieldItemDataDefinition) {
      return 'getFieldElement';
    }
    elseif ($definition instanceof ListDataDefinitionInterface) {
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
   * @param array $options
   *   (Optional) Values TODO.
   *
   * @return array
   *   A render array.
   *
   * @throws PluginNotFoundException
   */
  public function getElementFor($plugin_id, $property_name = '', $options = []) {
    try {
      $definition = $this->typedDataManager->createDataDefinition($plugin_id);

      $method = $this->getMethod($definition);
      $element = $this->{$method}($definition, $property_name, $options);
    }
    catch (PluginNotFoundException $e) {
      throw $e;
    }
    return $element;
  }

  /**
   * Create a render element for the given data type.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The typed data to generate a render element for.
   *
   * @return array
   *   A render element.
   *
   * @throws PluginNotFoundException
   */
  public function getElementForData(TypedDataInterface $data) {
    try {
      $definition = $data->getDataDefinition();
      $method = $this->getMethod($definition);

      $element = $this->{$method}($definition);
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
   *   The typed data definition.
   *
   * @return array
   *   A form element.
   */
  public function getPrimitiveElement(DataDefinitionInterface $definition) {
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
   *
   * @return array
   *   A form element.
   */
  public function getComplexElement(ComplexDataDefinitionInterface $parent_definition, $property_name = '') {
    if ($property_name) {
      /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
      $definition = $parent_definition->getPropertyDefinition($property_name);
      $method = $this->getMethod($definition);
      return $this->{$method}($definition);
    }

    $element = [];
    $definitions = $parent_definition->getPropertyDefinitions();

    // Create a container element for the complex data type.
    if (count($definitions) > 1) {
      $element = $this->getParentContainer($parent_definition, 'fieldset');
    }

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    foreach ($definitions as $name => $definition) {
      if ($this->shouldDisplay($definition)) {
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
   *   The field item data definition.
   * @param string $property_name
   *   (Optional) A property name to restrict the form element return value.
   *
   * @return array
   *   A form element.
   *
   * @todo write a unit test for this but this requires a test field item that
   *   does not suck because all of the field items depend on \t(). FML.
   */
  public function getFieldElement(FieldItemDataDefinition $field_definition, $property_name = '') {
    if ($property_name) {
      /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
      $definition = $field_definition->getPropertyDefinition($property_name);
      $method = $this->getMethod($definition);
      return $this->{$method}($definition);
    }

    $element = [];
    $definitions = $field_definition->getPropertyDefinitions();

    // Create a container element for the complex data type.
    if (count($definitions) > 1) {
      $element = $this->getParentContainer($field_definition);
    }

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    foreach ($definitions as $name => $definition) {
      if ($this->shouldDisplay($definition)) {
        $method = $this->getMethod($definition);
        $element[$name] = $this->{$method}($definition);
      }
    }

    return $element;
  }

  /**
   * Get a form elemetn for a base field definition.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $definition
   *   The base field definition.
   *
   * @return array
   *   A form element.
   */
  public function getBaseFieldElement(BaseFieldDefinition $definition) {
    $element = $this->getParentContainer($definition, 'fieldgroup');
    $settings = $definition->getSettings();

    // Detect entity reference fields and do something about it.
    if (isset($settings['target_type'])) {
      $element[0] = [
        '#type' => 'entity_autocomplete',
        '#title' => $definition->getLabel(),
        '#target_type' => $settings['target_type'],
      ];
      return $element;
    }

    $method = $this->getMethod($definition->getItemDefinition());
    $element[0] = $this->{$method}($definition->getItemDefinition());

    return $element;
  }

  /**
   * Get an element for an entity type.
   *
   * @param \Drupal\Core\Entity\TypedData\EntityDataDefinition $entity_definition
   *   The entity data definition.
   * @param string $property_name
   *   (Optional) an optional property name to restrict to.
   * @param array $options
   *   (Optional) values TODO.
   *
   * @return array
   *   A form element.
   */
  public function getEntityElement(EntityDataDefinition $entity_definition, $property_name = '', $options = []) {

    // Return the element corresponding to the property name before building an
    // entire entity form.
    if ($property_name) {
      $definition = $entity_definition->getPropertyDefinition($property_name);

      if ($definition) {
        $method = $this->getMethod($definition);
        return $this->{$method}($definition);
      }
    }

    $entity_type = $entity_definition->getEntityTypeId();
    $form_state = new FormState();

    try {
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type, FALSE);
      if ($entity_type_definition && $entity_type_definition->hasHandlerClass('form', 'default')) {
        $form = $this->entityTypeManager->getFormObject($entity_type, 'default');
        $form->setEntity($this->entityTypeManager->getStorage($entity_type)
          ->create($options));
        $element = $form->buildForm([], $form_state);

        // Remove actions.
        unset($element['actions']);
      }
      else {
        $element = $this->getComplexElement($entity_definition);
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      // Fallback to complex data definition.
      return $this->getComplexElement($entity_definition);
    }
    catch (EntityStorageException $e) {
      return $this->getComplexElement($entity_definition);
    }

    return ($property_name && isset($element[$property_name])) ? $element[$property_name] : $element;
  }

  /**
   * Set the nonRequiredProperties property.
   *
   * @param bool $value
   *   TRUE if non-required properties should be in the returned element.
   *
   * @return $this
   */
  public function setNonRequiredProperties($value) {
    $this->nonRequiredProperties = (bool) $value;

    return $this;
  }

  /**
   * Get the nonRequiredProperties property.
   *
   * @return bool
   *   TRUE if the nonRequiredProperties property is TRUE.
   */
  public function getNonRequiredProperties() {
    return $this->nonRequiredProperties;
  }

  /**
   * Set the readOnlyProperties property.
   *
   * @param bool $value
   *   TRUE if read-only properties should be in the returned element.
   *
   * @return $this
   */
  public function setDisplayReadOnly($value) {
    $this->readOnlyProperties = (bool) $value;

    return $this;
  }

  /**
   * Get the readOnlyProperties property.
   *
   * @return bool
   *   TRUE if the readOnlyProperties property is TRUE.
   */
  public function getDisplayReadOnly() {
    return $this->readOnlyProperties;
  }

  /**
   * Get an element container for a complex data definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition to generate a parent element for.
   * @param string $type
   *   The type of parent element to use such as fieldset or container.
   *
   * @return array
   *   A form element.
   */
  protected function getParentContainer(DataDefinitionInterface $definition, $type = 'container') {
    $element = [
      '#type' => $type,
      '#tree' => TRUE,
    ];

    if ($type === 'fieldgroup') {
      $element['#description'] = $definition->getDescription() ? $definition->getDescription() : '';
      $element['#title'] = $definition->getLabel() ? $definition->getLabel() : '';
    }
    elseif ($type === 'fieldset' && !$definition->getLabel()) {
      $element['#type'] = 'container';
    }
    elseif ($type === 'fieldset') {
      $element['#description'] = $definition->getDescription() ? $definition->getDescription() : '';
      $element['#title'] = $definition->getLabel();
    }

    return $element;
  }

  /**
   * Get an element container for a list of items.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $definition
   *   The list data definition.
   *
   * @return array
   *   A form elemnet.
   *
   * @todo implement an add_more functionality.
   */
  public function getListElement(ListDataDefinitionInterface $definition) {
    $element = $this->getParentContainer($definition, 'fieldset');

    $property_definition = $definition->getItemDefinition();
    $method = $this->getMethod($property_definition);
    $element[] = $this->{$method}($property_definition);

    return $element;
  }

  /**
   * Check whether or not to include the property in the returned element.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition to check whether or not to display.
   *
   * @return bool
   *   TRUE if the property should be displayed. Default to TRUE.
   */
  protected function shouldDisplay(DataDefinitionInterface $definition) {
    $ret = TRUE;

    if ($definition->isComputed()) {
      $ret = FALSE;
    }
    elseif ($definition->isReadOnly() && (!$this->nonRequiredProperties || !$this->readOnlyProperties)) {
      $ret = FALSE;
    }
    elseif (!$definition->isRequired() && !$this->nonRequiredProperties) {
      $ret = FALSE;
    }

    return $ret;
  }

  /**
   * Get any additional form properties to merge into the element.
   *
   * @param string $type
   *   The form element type property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $parent_type
   *   (Optional) The parent data type.
   *
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
