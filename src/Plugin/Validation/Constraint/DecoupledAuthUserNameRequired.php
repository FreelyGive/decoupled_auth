<?php

/**
 * @file
 * Contains \Drupal\decoupled_auth\Plugin\Validation\Constraint\DecoupledAuthUserNameRequired.
 */

namespace Drupal\decoupled_auth\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Checks if the user's username is provided if required.
 *
 * The user name field is NOT required if the user doesn't have other
 * authentication information such as a password. We could also check against
 * the username on the unchanged entity, but this would introduce complications
 * when decoupling or acquiring an exiting user.
 *
 * @Constraint(
 *   id = "DecoupledAuthUserNameRequired",
 *   label = @Translation("User name required", context = "Validation")
 * )
 */
class DecoupledAuthUserNameRequired extends Constraint implements ConstraintValidatorInterface {

  /**
   * Violation message. Use the same message as FormValidator.
   *
   * Note that the name argument is not sanitized so that translators only have
   * one string to translate. The name is sanitized in self::validate().
   *
   * @var string
   */
  public $message = '@name field is required.';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    /** @var \Drupal\user\UserInterface $account */
    $account = $items->getEntity();
    $pass = $account->pass->value

    if (!empty($pass) && (!isset($items) || $items->isEmpty())) {
      $this->context->addViolation($this->message, ['@name' => $account->getFieldDefinition('name')->getLabel()]);
    }
  }

}
