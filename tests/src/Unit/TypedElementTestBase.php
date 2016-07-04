<?php

namespace Drupal\Tests\typed_widget\Unit;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpKernel\Log\NullLogger;

/**
 * Base test class for testing typed element builder.
 */
abstract class TypedElementTestBase extends UnitTestCase {

  /**
   * Set the container. Required in all child tests.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   Set the Drupal container with some useful services.
   */
  protected function setContainer(TypedDataManagerInterface $typedDataManager) {
    $container = new ContainerBuilder();
    $container->set('logger_factory', $this->getLogger());
    $container->set('module_handler', $this->getModuleHandlerMock());
    $container->set('typed_data_manager', $typedDataManager);
    $container->set('entity_type.manager', $this->getEntityTypeManagerMock());
    \Drupal::setContainer($container);
  }

  /**
   * Get a dummy entity type manager mock.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function getEntityTypeManagerMock() {
    $prophecy = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    return $prophecy->reveal();
  }

  /**
   * Get a dummy logger channel mock.
   *
   * @return \Drupal\Core\Logger\LoggerChannelFactoryInterface
   *   The logger channel object.
   */
  protected function getLogger() {
    $loggerProphecy = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerProphecy->get('typed_widget')->willReturn(new NullLogger());
    return $loggerProphecy->reveal();
  }

  /**
   * Get a dummy module handler mock.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module_handler service.
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
