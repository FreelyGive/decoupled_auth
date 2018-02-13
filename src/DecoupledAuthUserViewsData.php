<?php

namespace Drupal\decoupled_auth;

use Drupal\profile\Entity\ProfileType;
use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the decoupled user entity type.
 */
class DecoupledAuthUserViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $types = ProfileType::loadMultiple();
    foreach ($types as $profile_type) {
      $table_key = 'user__profile_' . $profile_type->id();
      $field_key = 'profile_' . $profile_type->id() . '_target_id';

      if (isset($data[$table_key][$field_key])) {
        $data[$table_key][$field_key]['relationship']['label'] = $this->t('Profile (@bundle)', ['@bundle' => $profile_type->label()]);
        $data[$table_key][$field_key]['relationship']['title'] = $this->t('Profile (@bundle)', ['@bundle' => $profile_type->label()]);
      }
    }

    return $data;
  }

}
