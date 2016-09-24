<?php


namespace Drupal\typed_widget;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Provides an interface for classes that are called from TypedElementBuilder.
 */
interface ElementBuilderInterface {

  /**
   * Get the element type from the data definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   *
   * @return string
   *   The element type.
   */
  public static function getType(DataDefinitionInterface $definition);

  /**
   * Get element properties for a given element type and data definition.
   *
   * @param string $type
   *   The element type.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $parent_type
   *   An optional parent element type.
   *
   * @return array
   *   An array of element properties to add to the element array.
   */
  public static function getProperties($type, DataDefinitionInterface $definition, $parent_type = '');

}
