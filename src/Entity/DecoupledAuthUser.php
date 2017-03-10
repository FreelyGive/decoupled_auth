<?php

namespace Drupal\decoupled_auth\Entity;

use Drupal\decoupled_auth\DecoupledAuthUserInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Defines the decoupled user authentication user entity class.
 */
class DecoupledAuthUser extends User implements DecoupledAuthUserInterface {

  /**
   * Flag to indicate whether this user has decoupled authentication.
   *
   * @var bool
   */
  protected $decoupled = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->calculateDecoupled();
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    /* @var $entities DecoupledAuthUser[] */
    parent::postLoad($storage, $entities);
    foreach ($entities as $entity) {
      $entity->calculateDecoupled();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDecoupled() {
    $this->decoupled = $this->name->value === NULL || $this->id() === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isCoupled() {
    return !$this->decoupled;
  }

  /**
   * {@inheritdoc}
   */
  public function isDecoupled() {
    return $this->decoupled;
  }

  /**
   * {@inheritdoc}
   */
  public function couple() {
    $this->decoupled = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function decouple() {
    $this->decoupled = TRUE;
    $this->name = NULL;
    $this->pass = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    // Only coupled users are authenticated.
    return $this->isCoupled();
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    $roles = parent::getRoles(TRUE);

    // Add the authenticated/anonymous roles.
    if (!$exclude_locked_roles) {
      if ($this->isAuthenticated()) {
        $roles[] = RoleInterface::AUTHENTICATED_ID;
      }
      elseif ($this->isAnonymous()) {
        $roles[] = RoleInterface::ANONYMOUS_ID;
      }
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Make name not required at a database level and swap the constraint.
    $constraints = $fields['name']->getConstraints();
    unset($constraints['UserName']);
    unset($constraints['NotNull']);
    $constraints['DecoupledAuthUserName'] = array();
    $fields['name']
      ->setRequired(FALSE)
      ->setConstraints($constraints);

    // Make adjustments to mail.
    $constraints = $fields['mail']->getConstraints();

    // Swap to our own unique constraint for mail.
    unset($constraints['UserMailUnique']);
    $constraints['DecoupledAuthUserMailUnique'] = array();

    // Swap to our own required constraint for mail.
    unset($constraints['UserMailRequired']);
    $constraints['DecoupledAuthUserMailRequired'] = array();

    $fields['mail']->setConstraints($constraints);

    return $fields;
  }

  /**
   * Update the fields referencing the given profile types for each user.
   *
   * @param string[] $types
   *   An array of entities.
   */
  public function updateProfileFields(array $types) {
    // Update the field for type.
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    foreach ($types as $type) {
      $this->{'profile_' . $type} = $profile_storage->loadMultipleByUser($this, $type, TRUE);
    }
    $this->save();
  }

}
