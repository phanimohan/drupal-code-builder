<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests for Form component.
 */
class ComponentForm8Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test generating a module with form.
   */
  public function testFormGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => array(
        0 => [
          'form_class_name' => 'MyForm',
          'injected_services' => [],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("src/Form/MyForm.php", $files, "The files list has a form class file.");

    $form_file = $files["src/Form/MyForm.php"];

    $this->assertWellFormedPHP($form_file);
    $this->assertDrupalCodingStandards($form_file);

    $this->parseCode($form_file);
    $this->assertHasClass('Drupal\test_module\Form\MyForm');
    $this->assertClassHasParent('Drupal\Core\Form\FormBase');
    $this->assertHasMethods(['getFormId', 'buildForm', 'submitForm']);

    // TODO: convert to PHP parser.
    $this->assertFunctionCode($form_file, 'getFormId', "return 'test_module_myform");
  }

  /**
   * Test Form component with injected services.
   */
  function testFormGenerationWithServices() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => array(
        0 => [
          'form_class_name' => 'MyForm',
          'injected_services' => [
            'current_user',
            'entity_type.manager',
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Two files are returned.");

    $form_file = $files["src/Form/MyForm.php"];

    $this->assertWellFormedPHP($form_file);
    $this->assertDrupalCodingStandards($form_file);

    $this->parseCode($form_file);
    $this->assertHasClass('Drupal\test_module\Form\MyForm');
    $this->assertClassHasParent('Drupal\Core\Form\FormBase');

    // Check service injection.
    $this->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
    ]);
  }

}
