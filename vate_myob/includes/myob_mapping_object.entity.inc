<?php

/**
 * @file
 * Contains MYOBMappingObject.
 */

/**
 * Entity class for MYOB Mapping Objects
 */
class MYOBMappingObject extends Entity {

  // @codingStandardsIgnoreStart
  public
    $myob_mapping_object_id,
    $myob_id,
    $myob_type,
    $entity_id,
    $entity_type,
    $created,
    $entity_updated,
    $last_sync,
    $last_sync_status,
    $order_id;
  // @codingStandardsIgnoreEnd

  /**
   * Constructor for MYOBMappingObject.
   *
   * @param array $values
   *   Associated array of values this entity should start with.
   */
  public function __construct(array $values = array()) {
    parent::__construct($values, 'myob_mapping_object');
  }

  /**
   * Save the entity.
   *
   * @return object
   *   The newly saved version of the entity.
   */
  public function save() {
    if (isset($this->is_new) && $this->is_new) {
      $this->created = REQUEST_TIME;
    }

    return parent::save();
  }

}
