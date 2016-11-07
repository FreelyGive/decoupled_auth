<?php

namespace Drupal\decoupled_auth;

use Symfony\Component\EventDispatcher\Event;
use Drupal\decoupled_auth\Entity\DecoupledAuthUser;

/**
 * Defines user acquisition events.
 */
class AcquisitionEvent extends Event {

  /**
   * Name of the event fired prior to attempting to find a user match.
   *
   * This event allows modules to make changes to the matched values and context
   * used for the acquisition attempt.
   *
   * @Event
   *
   * @see \Drupal\decoupled_auth\AcquisitionService::acquire()
   *
   * @var string
   */
  const PRE = 'decoupled_auth.pre_acquire';

  /**
   * Name of the event fired after attempting to find a user match.
   *
   * This event allows modules to react to an acquisition success for failure.
   *
   * @Event
   *
   * @see \Drupal\decoupled_auth\AcquisitionService::acquire()
   *
   * @var string
   */
  const POST = 'decoupled_auth.post_acquire';

  /**
   * The values to match on.
   *
   * @var array
   */
  protected $values;

  /**
   * The context for the acquisition.
   *
   * @var array
   */
  protected $context;

  /**
   * The acquired user, if any.
   *
   * @var \Drupal\decoupled_auth\Entity\DecoupledAuthUser|NULL
   */
  protected $user;

  /**
   * Constructs a new AcquisitionEvent.
   */
  public function __construct(array &$values, array &$context, DecoupledAuthUser $user = NULL) {
    $this->values = &$values;
    $this->context = &$context;
    $this->user = $user;
  }

  /**
   * Returns the values by reference.
   *
   * @return array
   *   The values to match on.
   */
  public function &getValues() {
    return $this->values;
  }

  /**
   * Returns the context by reference.
   *
   * @return array
   *   The context for the acquisition.
   */
  public function &getContext() {
    return $this->context;
  }

  /**
   * Return the name, if any, of the acquisition attempt.
   *
   * @return string|null
   *   The acquired context name or NULL if none provided.
   */
  public function getName() {
    return $this->context['name'];
  }

  /**
   * Returns the user, if any.
   *
   * @return \Drupal\decoupled_auth\Entity\DecoupledAuthUser|null
   *   The acquired user or NULL if none found.
   */
  public function &getUser() {
    return $this->user;
  }

}
