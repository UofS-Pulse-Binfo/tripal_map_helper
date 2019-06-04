<?php

namespace Tests;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;
use Faker\Factory;

/**
 * @class
 * Load MSTmap files.
 */
class MstImporterTest extends TripalTestCase {
  // Uncomment to auto start and rollback db transactions per test method.
  use DBTransaction;

  /**
   * Test MSTmapImporter::loadMapMetadata().
   *
   * @dataProvider provideMapMetadata
   */
  public function testLoadMapMetadata($args) {
    $file = ['file_local' => __DIR__ . '/example_files/single_linkage_group_mst.txt'];

    // Run the function.
    module_load_include('inc', 'tripal_genetic', 'includes/TripalImporter/MSTmapImporter');
    $importer = new \MSTmapImporter();
    $importer->create($run_args, $file);
    $importer->loadMapMetadata($args);

    // Check the featuremap was created.
    $map = chado_select_record('featuremap', ['featuremap_id'], [
      'name' => $args['featuremap_name'],
      'unittype_id' => ['name' => $args['featuremap_unittype_name']],
    ]);
    $this->assertNotEmpty($map,
      "Unable to find featuremap record with name " . $args['featuremap_name']);

    // Check the analysis was created.
    $analysis = chado_select_record('analysis', ['analysis_id'], [
      'program' => $args['analysis_program'],
      'programversion' => $args['analysis_programversion'],
      'description' => $args['analysis_description'],
    ]);
    $this->assertNotEmpty($analysis,
      "Unable to find analysis for featuremap " . $args['featuremap_name']);

    // And connected to the current featuremap.
    // @todo can't yet since featuremap_analysis doesn't exist.

  }

  /**
   * Data Provider to test the loading functions.
   */
  public function provideMapMetadata() {
    $faker = Factory::create();
    $set = [];

    // Comprehensive (all form elements filled out.
    $set[] = [
      [
        'featuremap_name' => $faker->words(4, TRUE),
        'pub_map_name' => $faker->words(5, TRUE),
        // @todo look up or fake an organism here.
        'organism_organism_id' => 1,
        'featuremap_unittype_name' => 'cM',
        'map_type' => 'linkage',
        'pop_type' => 'F2',
        'pop_size' => $faker->randomDigitNotNull(),
        'analysis_program' => $faker->name,
        'analysis_programversion' => $faker->randomFloat(2, 1, 5),
        'analysis_description' => $faker->sentences(2, TRUE),
        'featuremap_description' => $faker->paragraphs(5, TRUE),
      ],
    ];

    // Only required.
    $set[] = [
      [
        'featuremap_name' => $faker->words(3, TRUE),
        // @todo look up or fake an organism here.
        'organism_organism_id' => 1,
        'analysis_program' => $faker->name,
        'analysis_programversion' => $faker->randomFloat(2, 1, 5),
        'map_type' => $faker->name,
        'featuremap_unittype_name' => 'cM',
      ],
    ];

    return $set;
  }

  /**
   * Test the form.
   */
  public function testLoaderForm() {
    $file = ['file_local' => __DIR__ . '/example_files/single_linkage_group_mst.txt'];

    // Run the function.
    module_load_include('inc', 'tripal_genetic', 'includes/TripalImporter/MSTmapImporter');
    $importer = new \MSTmapImporter();
    $importer->create($run_args, $file);

    $form = [];
    $form_state = [];
    $form = $importer->form($form, $form_state);

    $this->assertNotEmpty($form,
      "Failed to ensure we have a form.");

    // Check all required fields are present.
    // DB Requirment:  analysis.program, analysis.programversion,
    // organism.organism_id.
    //
    // Tripal Map Required: featuremap.name, featuremapprop.value (map_type),
    // featuremap.unittype_id.
    $required = [
      'organism_organism_id', 'analysis_program', 'analysis_programversion',
      'featuremap_name', 'map_type', 'featuremap_unittype_name',
    ];
    foreach ($form as $key => $element) {
      if (isset($element['#required']) and $element['#required']) {
        $this->assertContains($key, $required,
          "Unexpected form element, $key, marked as required.");
      }
      else {
        $this->assertNotContains($key, $required,
          "Required field, $key, not marked as required.");
      }
    }
  }

  /**
   * Test that run() runs...
   */
  public function testRun() {
    $file = ['file_local' => __DIR__ . '/example_files/single_linkage_group_mst.txt'];
    $args = [
      'featuremap_name' => 'Lazy Map',
      // @todo look up or fake an organism here.
      'organism_organism_id' => 1,
      'analysis_program' => 'MSTmap',
      'analysis_programversion' => 'unknown',
    ];

    // Run the function.
    module_load_include('inc', 'tripal_genetic', 'includes/TripalImporter/MSTmapImporter');
    $importer = new \MSTmapImporter();
    $importer->create($args, $file);

    // Supress tripal errors.
    putenv("TRIPAL_SUPPRESS_ERRORS=TRUE");
    ob_start();

    $success = $importer->run();

    // Clean the buffer and unset tripal errors suppression.
    ob_end_clean();
    putenv("TRIPAL_SUPPRESS_ERRORS");

    $this->assertNotFalse($success,
      "The run function should execute without errors.");
  }

}
