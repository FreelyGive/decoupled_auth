<?php

namespace Drupal\decoupled_auth\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserNameConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the DecoupledAuthUserName constraint.
 */
class DecoupledAuthUserNameValidator extends UserNameConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    /** @var \Drupal\decoupled_auth\DecoupledAuthUserInterface $account */
    // If this account is decoupled.
    $account = $items->getEntity();

    // If we are decoupled, we must not have a name.
    if ($account->isDecoupled()) {
      if (isset($items) && $items->value) {
        $this->context->addViolation($constraint->decoupledNotEmptyMessage);
      }
    }
    // Otherwise, pass onto the parent to validate as a normal user.
    else {
      parent::validate($items, $constraint);
    }
  }

}
