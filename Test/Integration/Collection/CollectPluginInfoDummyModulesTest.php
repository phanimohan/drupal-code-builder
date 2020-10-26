<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests plugin collection with a dummy module.
 */
class CollectPluginInfoDummyModulesTest extends KernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    // Don't enable any modules, as we replace the module extension list during
    // the test and remove all modules except for our fixture module.
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Drupal doesn't know about DCB, so won't have it in its autoloader, so
    // rely on the Factory file's autoloader.
    $dcb_root = dirname(dirname(dirname(__DIR__)));
    require_once("$dcb_root/Factory.php");

    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);

    $this->environment = \DrupalCodeBuilder\Factory::getEnvironment();

    $this->pluginTypesCollector = new \DrupalCodeBuilder\Task\Collect\PluginTypesCollector(
      \DrupalCodeBuilder\Factory::getEnvironment(),
      new \DrupalCodeBuilder\Task\Collect\ContainerBuilderGetter,
      new \DrupalCodeBuilder\Task\Collect\MethodCollector,
      new \DrupalCodeBuilder\Task\Collect\CodeAnalyser($this->environment)
    );

    // Hack the task handler so we can call the processing method with a subset
    // of plugin manager service IDs.
    $class = new \ReflectionObject($this->pluginTypesCollector);
    $this->gatherPluginTypeInfoMethod = $class->getMethod('gatherPluginTypeInfo');
    $this->gatherPluginTypeInfoMethod->setAccessible(TRUE);

    parent::setUp();
  }

  protected function getPluginTypeInfoFromCollector($job) {
    return $this->gatherPluginTypeInfoMethod->invoke($this->pluginTypesCollector, [$job]);
  }

  /**
   * Tests plugin base class in the obvious location when there are no plugins.
   *
   * This uses a fixture Drupal module which was generated by DCB.
   *
   * TODO: add this to the stuff that DCB can generate automatically so it can
   * be updated at the same time as test sample data.
   */
  public function testObviousBaseClassDetection() {
    // Create a module list service, using our subclass that lets us hack in
    // the discovery.
    $module_list = new TestModuleExtensionList(
      $this->container->get('app.root'),
      'module',
      $this->container->get('cache.default'),
      $this->container->get('info_parser'),
      $this->container->get('module_handler'),
      $this->container->get('state'),
      $this->container->get('config.factory'),
      $this->container->get('extension.list.profile'),
      $this->container->getParameter('install_profile'),
      $this->container->getParameter('container.modules')
    );

    // Mock the discovery to return only our fixture module.
    $extension_discovery = $this->prophesize(\Drupal\Core\Extension\ExtensionDiscovery::class);

    // We expect DCB to be in the vendor folder.
    $extension_scan_result['test_generated_plugin_type'] = new \Drupal\Core\Extension\Extension(
      // Our module is outside of the Drupal root, but we have to specify it
      // as ModuleInstaller::install() assumes it when it constructs the
      // Extension object again later.
      \Drupal::root(),
      'test_generated_plugin_type',
      // This has to be a path relative to the given root in the first
      // parameter.
      '../vendor/drupal-code-builder/drupal-code-builder/Test/Fixtures/modules/test_generated_plugin_type/test_generated_plugin_type.info.yml'
    );
    $extension_discovery->scan('module')->willReturn($extension_scan_result);

    // Set the discovery on the module list and set it into the container.
    $module_list->setExtensionDiscovery($extension_discovery->reveal());
    $module_list->reset();
    $this->container->set('extension.list.module', $module_list);

    // Install our module.
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['test_generated_plugin_type']);

    $plugin_types_info = $this->getPluginTypeInfoFromCollector(
      [
        'service_id' => 'plugin.manager.test_generated_plugin_type_test_annotation_plugin',
        'type_id' => 'test_generated_plugin_type_test_annotation_plugin',
      ],
    );

    $this->assertEquals('Drupal\test_generated_plugin_type\Plugin\TestAnnotationPlugin\TestAnnotationPluginBase', $plugin_types_info['test_generated_plugin_type_test_annotation_plugin']['base_class']);
  }

}

/**
 * Module List which allows the discovery to be set.
 */
class TestModuleExtensionList extends \Drupal\Core\Extension\ModuleExtensionList {

  /**
   * @var \Drupal\Core\Extension\ExtensionDiscovery|null
   */
  protected $extensionDiscovery;

  /**
   * @param \Drupal\Core\Extension\ExtensionDiscovery $extension_discovery
   */
  public function setExtensionDiscovery(\Drupal\Core\Extension\ExtensionDiscovery $extension_discovery) {
    $this->extensionDiscovery = $extension_discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensionDiscovery() {
    return $this->extensionDiscovery ?: parent::getExtensionDiscovery();
  }

}
