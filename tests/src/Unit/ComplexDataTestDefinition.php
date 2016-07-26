<?php

/*
 * @file
 * Contains ComplexDataTestDefinition
 */

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * A fixture class that extends complex data definition base with property
 * definitions for the typed_widget test cases.
 */
class ComplexDataTestDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['test1'] = DataDefinition::create('string')
        ->setLabel('Test 1')
        ->setRequired(TRUE);
      $info['test2'] = DataDefinition::create('string')
        ->setLabel('Test 2');
    }
    return $info;
  }
}
