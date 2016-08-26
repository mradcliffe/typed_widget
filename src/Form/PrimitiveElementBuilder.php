<?php

namespace Drupal\typed_widget\Form;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\typed_widget\ElementBuilderInterface;

/**
 * Create element for primitive data types.
 */
class PrimitiveElementBuilder implements ElementBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public static function getType(DataDefinitionInterface $definition) {
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
    elseif (!empty($definition->getConstraints())) {
      $options = array_reduce($definition->getConstraints(), function(&$result, $item) {
        if (isset($item['choices'])) {
          $result = $item['choices'];
        }
        return $result;
      }, []);

      if (!empty($options)) {
        $type = 'select';
      }
    }

    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public static function getProperties($type, DataDefinitionInterface $definition, $parent_type = '') {
    $properties = [];

    $implementations = class_implements($definition->getClass());

    if ($type === 'select') {
      // Add the Constraint options to the select element.
      $properties['#options'] = array_reduce($definition->getConstraints(), function(&$result, $constraint) {
        if (isset($constraint['choices'])) {
          $result = $constraint['choices'];
        }
        return $result;
      }, []);
    }
    elseif ($type === 'number') {
      $options = $definition->getConstraint('Range');
      if ($options) {
        $properties['#min'] = $options['min'];
        $properties['#max'] = $options['max'];
      }
      elseif (in_array('Drupal\Core\TypedData\Type\DurationInterface', $implementations)) {
        $properties['#min'] = 0;
        // @todo constant
        $properties['#max'] = 86400;
      }
    }

    return $properties;
  }

}
