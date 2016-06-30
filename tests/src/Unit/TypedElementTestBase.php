<?php

/**
 * @file
 * Contains TypedElementTestBase
 */

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Log\NullLogger;

/**
 * Class TypedElementTestBase
 */
abstract class TypedElementTestBase extends UnitTestCase {

  /**
   * Set the container. Required in all child tests.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   */
  protected function setContainer($typedDataManager) {
    $container = new ContainerBuilder();
    $container->set('logger_factory', $this->getLogger());
    $container->set('module_handler', $this->getModuleHandlerMock());
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);
  }

  /**
   * @return \Drupal\Core\Logger\LoggerChannelFactoryInterface;
   */
  protected function getLogger() {
    $loggerProphecy = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerProphecy->get('typed_widget')->willReturn(new NullLogger());
    return $loggerProphecy->reveal();
  }

  /**
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected function getModuleHandlerMock() {
    $handlerProphecy = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    return $handlerProphecy->reveal();
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
      /** $definition \Drupal\Core\TypedData\ComplexDataDefinitionInterface $definition */
      foreach ($definition->getPropertyDefinitions() as $name => $child_definition) {
        $typedDataProphecy->createDataDefinition($child_definition->getDataType())
          ->willReturn($child_definition);
        $typedDataProphecy->getDefaultConstraints($child_definition)
          ->willReturn([]);
        $typedDataProphecy->getDefinition($child_definition->getDataType())
          ->willReturn($child_definition);
      }
    }
    elseif ($definition instanceof ListDataDefinitionInterface) {
      $typedDataProphecy->createDataDefinition('string')
        ->willReturn($definition->getItemDefinition());
      $typedDataProphecy->getDefaultConstraints($definition->getItemDefinition())
        ->willReturn([]);
      $typedDataProphecy->getDefinition('string')
        ->willReturn($definition->getItemDefinition());
    }

    return $typedDataProphecy->reveal();
  }
}
