<?php

namespace Drupal\decoupled_auth\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\profile\Tests\ProfileTestTrait;

/**
 * Tests CRM fields and views.
 *
 * @group decoupled_auth
 */
class SimpleCrmTest extends WebTestBase {

  use ProfileTestTrait;
  use DecoupledAuthUserCreationTrait;

  public static $modules = ['user', 'decoupled_auth_crm', 'views'];

  /**
   * Testing admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $indivUser;

  /**
   * Testing admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Check that the required permissions exist.
    $this->checkPermissions([
      "view own crm_indiv profile",
      "add own crm_indiv profile",
      "edit own crm_indiv profile",
    ]);

    // Create a user with permissions for the crm_indiv profile type.
    $this->indivUser = $this->createUser();
    $this->indivUser->addRole('crm_indiv');
    $this->indivUser->save();
    $this->adminUser = $this->createUser([], NULL, TRUE);

  }

  /**
   * Function to get information about users from the Admin People view.
   *
   * @return mixed
   *   An associative array of user information or FALSE if not users are found.
   */
  protected function getCoupledUsersFromView() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/people');
    $this->assertResponse(200);

    $result = $this->xpath('//table[contains(@class, "responsive-enabled")]/tbody/tr');
    $result_accounts = array();
    foreach ($result as $account) {
      $name = (string) $account->td[1]->a;
      $roles = array();
      if (isset($account->td[3]->div->ul)) {
        foreach ($account->td[3]->div->ul->li as $element) {
          $roles[] = (string) $element;
        }
      }

      foreach ($account->td[1]->a->attributes() as $label => $attribute) {
        if ($label == 'href') {
          $href = explode('/', $attribute);
          $uid = array_pop($href);
        }
      }

      if (isset($uid)) {
        $result_accounts[$uid] = array(
          'name' => $name,
          'status' => (string) $account->td[2],
          'roles' => $roles,
          'member_for' => (string) $account->td[4],
          'last_access' => (string) $account->td[5],
          'uid' => $uid,
        );
      }
    }

    return !empty($result_accounts) ? $result_accounts : FALSE;
  }

  /**
   * Function to get information about users from the Contacts view.
   *
   * @return mixed
   *   An associative array of user information or FALSE if not users are found.
   */
  protected function getDecoupledUsersFromView() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/people/contacts');
    $this->assertResponse(200);

    $result = $this->xpath('//table[contains(@class, "views-table")]/tbody/tr');
    $result_accounts = array();

    foreach ($result as $account) {
      $email = (string) $account->td[1];
      $email_components = explode('@', $email);
      $name = $email_components[0];

      $roles = array();
      if (isset($account->td[3]->div->ul)) {
        foreach ($account->td[3]->div->ul->li as $element) {
          $roles[] = (string) $element;
        }
      }

      foreach ($account->td[5]->a->attributes() as $label => $attribute) {
        if ($label == 'href') {
          $href = substr($attribute, 0, strpos($attribute, '?') - 5);
          $href = explode('/', $href);
          $uid = array_pop($href);
        }
      }

      if (isset($uid)) {
        $result_accounts[$uid] = array(
          'name' => $name,
          'email' => $email,
          'roles' => $roles,
          'uid' => $uid,
        );
      }
    }
    return !empty($result_accounts) ? $result_accounts : FALSE;
  }

  /**
   * Test People and Contact views.
   */
  public function testContactViews() {
    // Create a decoupled user to test views.
    $user_1 = $this->createDecoupledUser();

    // Fetch the user accounts from both views.
    $coupled_accounts = $this->getCoupledUsersFromView();
    $decoupled_accounts = $this->getDecoupledUsersFromView();

    // Check decoupled user is in contacts view and not people view.
    $this->assertFalse(in_array($user_1->id(), array_keys($coupled_accounts)));
    $this->assertTrue(in_array($user_1->id(), array_keys($decoupled_accounts)));

    // Check coupled users are in people view and not contacts view.
    $this->assertTrue(in_array($this->indivUser->id(), array_keys($coupled_accounts)));
    $this->assertTrue(in_array($this->adminUser->id(), array_keys($coupled_accounts)));
    $this->assertFalse(in_array($this->indivUser->id(), array_keys($decoupled_accounts)));
    $this->assertFalse(in_array($this->adminUser->id(), array_keys($decoupled_accounts)));
  }

  /**
   * Test Simple Crm fields.
   */
  public function testContactFields() {
    $this->drupalLogin($this->indivUser);

    // Save a new picture.
    $image = current($this->drupalGetTestFiles('image'));
    $path = $this->container->get('file_system')->realpath($image->uri);

    // Submit the profile add form for an crm_indiv profile.
    $edit = [
      'crm_name[0][value]' => $this->indivUser->getDisplayName(),
      'crm_email[0][value]' => $this->indivUser->getEmail(),
      'crm_dob[0][value][year]' => '1990',
      'crm_dob[0][value][month]' => '1',
      'crm_dob[0][value][day]' => '1',
      'crm_gender' => 'female',
      'files[crm_photo_0]' => $path,
    ];
    $this->drupalPostForm('user/' . $this->indivUser->id() . '/crm_indiv', $edit, t('Save'));

    // Check that the fields have been saved.
    $this->drupalGet('user/' . $this->indivUser->id() . '/crm_indiv');
    $this->assertResponse(200);

    $this->assertFieldByName('crm_name[0][value]', $this->indivUser->getDisplayName());
    $this->assertFieldByName('crm_email[0][value]', $this->indivUser->getEmail());
    $this->assertFieldByName('crm_dob[0][value][year]', '1990');
    $this->assertFieldByName('crm_dob[0][value][month]', '1');
    $this->assertFieldByName('crm_dob[0][value][day]', '1');
    $this->assertFieldByName('crm_gender', 'female');
    $this->assertRaw($image->filename);
  }

}
