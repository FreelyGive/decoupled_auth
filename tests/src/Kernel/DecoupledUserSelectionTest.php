<?php

/**
 * @file
 * Contains Drupal\Tests\decoupled_auth\Kernel\DecoupledUserSelectionTest.
 */

namespace Drupal\Tests\decoupled_auth\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\decoupled_auth\Tests\DecoupledAuthUserCreationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests entity reference selection plugins.
 *
 * @group decoupled_auth
 */
class DecoupledUserSelectionTest extends KernelTestBase {

  use UserCreationTrait;
  use DecoupledAuthUserCreationTrait;

  /**
   * The selection handler.
   *
   * @var \Drupal\decoupled_auth\Plugin\EntityReferenceSelection\DecoupledUserSelection.
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'decoupled_auth', 'field', 'entity_reference'];

  /**
   * @var User
   *
   * Admin user for permissions check.
   */
  protected $adminUser;

  /**
   * @var User
   *
   * Decoupled user without user role.
   */
  protected $decoupledUserNoRole;

  /**
   * @var User
   *
   * Decoupled user with user role.
   */
  protected $decoupledUserRole;

  /**
   * @var User
   *
   * Coupled user without user role.
   */
  protected $coupledUserNoRole;

  /**
   * @var User
   *
   * Coupled user with user role.
   */
  protected $coupledUserRole;

  /**
   * @var string
   *
   * The rid of the filter role.
   */
  protected $userRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['decoupled_auth']);
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->userRole = $this->createRole([]);
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->setSelectionHandler();
  }

  /**
   * Updates the selection handler.
   *
   * @param bool|FALSE $filter
   *   Whether or not the handler should filter by user role.
   */
  protected function setSelectionHandler($filter = FALSE) {
    $options = [
      'target_type' => 'user',
      'handler' => 'decoupled_auth_user',
      'handler_settings' => [
        'include_decoupled' => 1,
      ],
    ];

    if ($filter) {
      $options['handler_settings']['filter'] = [
        'type' => 'role',
        'role' => [
          $this->userRole => $this->userRole,
        ]
      ];
    }

    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance($options);
  }

  /**
   * Creates a range of users to test against.
   */
  protected function createTestUsers() {
    // Decoupled users (with and without user role).
    $this->decoupledUserNoRole = $this->createDecoupledUser();

    $this->decoupledUserRole = $this->createDecoupledUser();
    $this->decoupledUserRole->activate();
    $this->decoupledUserRole->addRole($this->userRole);
    $this->decoupledUserRole->save();

    // Coupled users (with and without user role).
    $this->coupledUserNoRole = $this->createUser();

    $this->coupledUserRole = $this->createUser();
    $this->coupledUserRole->addRole($this->userRole);
    $this->coupledUserRole->save();
  }

  /**
   * Sets the current account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  protected function setCurrentAccount(AccountInterface $account) {
    $this->container->get('account_switcher')->switchTo($account);
  }

  /**
   * Testing selection handler class.
   */
  public function testSelectionHandlerClass() {
    $this->assertEquals(get_class($this->selectionHandler), 'Drupal\decoupled_auth\Plugin\EntityReferenceSelection\DecoupledUserSelection');
  }


  /**
   * Testing selection handler results without role filter.
   *
   * All users should be referenceable without role filter.
   * Decoupled users should only be referenceable for the admin user.
   */
  public function testSelectionHandlerResults() {
    // Create the demo users.
    $this->createTestUsers();

    // Get selection users.
    $groups = $this->selectionHandler->getReferenceableEntities();
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertTrue(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->coupledUserRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserRole->id(), $gids));

    // Switch to admin user.
    $this->setCurrentAccount($this->adminUser);

    // Get selection users.
    $groups = $this->selectionHandler->getReferenceableEntities();
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertTrue(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->coupledUserRole->id(), $gids));
    $this->assertTrue(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->decoupledUserRole->id(), $gids));
  }

  /**
   * Testing selection handler results with role filter.
   *
   * Only user with the correct role should be referenceable.
   * Decoupled users should only be referenceable for the admin user.
   */
  public function testSelectionHandlerRolesResults() {
    // Create the demo users and use the handler with role filter.
    $this->setSelectionHandler(TRUE);
    $this->createTestUsers();

    // Get selection users.
    $groups = $this->selectionHandler->getReferenceableEntities();
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertFalse(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->coupledUserRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserRole->id(), $gids));

    // Switch to admin user.
    $this->setCurrentAccount($this->adminUser);

    // Get selection users.
    $groups = $this->selectionHandler->getReferenceableEntities();
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertFalse(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->coupledUserRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->decoupledUserRole->id(), $gids));
  }

  /**
   * Testing selection handler results with matches.
   *
   * Check matching works on username and email for decoupled and coupled users.
   */
  public function testSelectionHandlerMatchResults() {
    // Create the demo users and use the handler with role filter.
    $this->createTestUsers();
    $this->setCurrentAccount($this->adminUser);

    // Get selection users with email match.
    $groups = $this->selectionHandler->getReferenceableEntities('@');
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertTrue(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->coupledUserRole->id(), $gids));
    $this->assertTrue(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertTrue(in_array($this->decoupledUserRole->id(), $gids));


    // Get selection users with username match.
    $groups = $this->selectionHandler->getReferenceableEntities($this->coupledUserNoRole->getAccountName());
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertTrue(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertFalse(in_array($this->coupledUserRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserRole->id(), $gids));

    // Get selection users with email prefix.
    $groups = $this->selectionHandler->getReferenceableEntities($this->decoupledUserNoRole->email_prefix);
    $gids = array_keys($groups['user']);

    // Check results.
    $this->assertFalse(in_array($this->coupledUserNoRole->id(), $gids));
    $this->assertFalse(in_array($this->coupledUserRole->id(), $gids));
    $this->assertTrue(in_array($this->decoupledUserNoRole->id(), $gids));
    $this->assertFalse(in_array($this->decoupledUserRole->id(), $gids));
  }

}
