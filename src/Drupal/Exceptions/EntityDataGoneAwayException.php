<?php

/**
 * @file
 * Contains EntityXliff\Drupal\Exceptions\EntityDataGoneAwayException.
 */

namespace EntityXliff\Drupal\Exceptions;

/**
 * Class EntityDataGoneAwayException
 *
 * Thrown when an EntityTranslatable is unable to load the underlying data in an
 * \EntityDrupalWrapper. This can happen when an import relies on the existence
 * of a node in a translation set which has gone out of phase due to a previous
 * database transaction rollback.
 *
 * Resolving the issue that caused the original database rollback will resolve
 * this problem as well.
 *
 * @package EntityXliff\Drupal\Exceptions
 */
class EntityDataGoneAwayException extends \RuntimeException {

}
