<?php

/**
 * @file
 * Contains \Drupal\decoupled_auth\AcquisitionService
 */

namespace Drupal\decoupled_auth;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface AcquisitionServiceInterface {

  /**
   * Behavior bit flag to indicate we should acquire the first user if there are
   * multiple potential matches.
   */
  const BEHAVIOR_FIRST = 0x1;

  /**
   * Behavior bit flag to indicate we should create a new user if there is no
   * match.
   */
  const BEHAVIOR_CREATE = 0x2;

  /**
   * Behavior bit flag to indicate we would prefer to have a coupled user (i.e.
   * has authentication details). Note this conflicts with $values['name'] and
   * may end with no results.
   */
  const BEHAVIOR_PREFER_COUPLED = 0x4;

  /**
   * Failure code for no values to acquire on.
   */
  const FAIL_NO_VALUES = 1;

  /**
   * Failure code for no matches.
   */
  const FAIL_NO_MATCHES = 2;

  /**
   * Failure code for multiple matches.
   */
  const FAIL_MULTIPLE_MATCHES = 3;

  /**
   * Constructs an AcquisitionServiceInterface object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher);

  /**
   * Create or acquire a party based off the given parameters.
   *
   * @param array $values
   *   An array of fields to match on. Keys are the field and values are the
   *   expected values. This can be any field on a user. If set to NULL,
   *   \Drupal\Core\Entity\Query\QueryInterface::notExists() will be used.
   * @param array $context
   *   An array of contextual information for the acquisition. The following
   *   key/value pairs are always available. Calling modules may
   *   add additional items specific to their process.
   *   - name: Optionally provide an identifying name for the process. This
   *     allows events to identify specific context. It is highly recommended
   *     that this be used.
   *   - conjunction: Which conjunction to use when there are multiple $values.
   *     This can be 'AND' or 'OR'. Defaults to 'AND'.
   *   - behavior: Flags indicating what should we do in the case of no or
   *     multiple matches.
   *   If the same service is used for multiple acquisitions, the context will
   *   be merged into the previous context.
   * @param string $method
   *   Optionally pass a variable to be filled with the acquisition method.
   *
   * @return \Drupal\decoupled_auth\DecoupledAuthUserInterface|NULL
   *   The acquired or newly created user or NULL on a failure.
   */
  public function acquire(array $values, array $context = NULL, &$method = NULL);

  /**
   * Return the failure code, if any, from the last operation.
   *
   * @return int|NULL
   *   One of the \Drupal\decoupled_auth\AcquisitionServiceInterface::FAIL_*
   *   constants, or NULL if there is no failure code.
   */
  public function getFailCode();

  /**
   * Get the context used for the last operation.
   *
   * @return array
   *   See \Drupal\decoupled_auth\AcquisitionServiceInterface::acquire()
   *   $context for details.
   */
  public function getContext();

}
