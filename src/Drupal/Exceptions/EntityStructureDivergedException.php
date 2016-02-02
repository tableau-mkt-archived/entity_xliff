<?php

/**
 * @file
 * Contains EntityXliff\Drupal\Exceptions\EntityStructureDivergedException.
 */

namespace EntityXliff\Drupal\Exceptions;

/**
 * Class EntityStructureDivergedException
 *
 * Thrown when the structure of a piece of content has diverged sufficiently
 * from the structure implied by an XLIFF on import such that the import cannot
 * continue.
 *
 * @package EntityXliff\Drupal\Exceptions
 */
class EntityStructureDivergedException extends \RuntimeException {

}
