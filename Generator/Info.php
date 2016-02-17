<?php

/**
 * @file
 * Contains generator classes for .info files.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator base class for module info file.
 *
 * This needs to go last in the order of subcomponents, so that it can see
 * all the files requested so far, and if required, declare them in the info
 * file.
 */
class Info extends File {

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files['info'] = array(
      'path' => '', // Means base folder.
      'filename' => $this->base_component->component_data['root_name'] . '.info',
      'body' => $this->file_body(),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
    return $files;
  }

}
