<?php

namespace Drupal\Tests\decoupled_auth\Functional;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\decoupled_auth\Tests\DecoupledAuthUserCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user password reset form for decoupled users.
 *
 * @group decoupled_auth
 *
 * @covers \Drupal\decoupled_auth\Form\UserPasswordFormAlter
 */
class UserPasswordFormTest extends BrowserTestBase {

  use DecoupledAuthUserCreationTrait;
  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'decoupled_auth',
  ];

  /**
   * Run the tests for core.
   *
   * @see \Drupal\Tests\decoupled_auth\Functional\UserPasswordFormTest::doTest
   *
   * @dataProvider data
   */
  public function testCore(array $users, $email, $expected_message, $expected_user_key = FALSE) {
    $this->doTest($users, $email, $expected_message, $expected_user_key);
  }

  /**
   * Run the tests for core.
   *
   * @see \Drupal\Tests\decoupled_auth\Functional\UserPasswordFormTest::doTest
   *
   * @dataProvider data
   */
  public function testUserRegistrationPassword(array $users, $email, $expected_message, $expected_user_key = FALSE) {
    try {
      $success = $this->container->get('module_installer')->install(['user_registrationpassword'], TRUE);
      $this->assertTrue($success, 'Enabled user_registrationpassword');
    }
    catch (MissingDependencyException $e) {
      // The exception message has all the details.
      $this->fail($e->getMessage());
    }

    $this->rebuildContainer();

    $this->doTest($users, $email, $expected_message, $expected_user_key);
  }

  /**
   * Data provider for ::testCore.
   *
   * @return array
   *   The test data.
   */
  public function data() {
    $data = [];

    $data['no-user'] = [
      'users' => [],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is not recognized as a username or an email address.',
      'expected_user_key' => FALSE,
    ];

    $data['only-decoupled'] = [
      'users' => [
        0 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is not recognized as a username or an email address.',
      'expected_user_key' => FALSE,
    ];

    $data['only-coupled'] = [
      'users' => [
        0 => ['decoupled' => FALSE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Status message Further instructions have been sent to your email address.',
      'expected_user_key' => 0,
    ];

    $data['decoupled_coupled'] = [
      'users' => [
        0 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
        1 => ['decoupled' => FALSE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Status message Further instructions have been sent to your email address.',
      'expected_user_key' => 1,
    ];

    $data['coupled_decoupled'] = [
      'users' => [
        0 => ['decoupled' => FALSE, 'email_prefix' => 'test'],
        1 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Status message Further instructions have been sent to your email address.',
      'expected_user_key' => 0,
    ];

    return $data;
  }

  /**
   * Run a password reset test scenario.
   *
   * @param array $users
   *   An array of users to create. Each user is an array of:
   *   - decoupled: Whether the user should be decoupled.
   *   - email_prefix: The email prefix to use, which will also be the name if
   *     coupled.
   * @param string $email
   *   The email address to enter on the form.
   * @param string $expected_message
   *   The expecte message on the form.
   * @param bool $expected_user_key
   *   If we are expecting a user match, the key from $users we expect to match.
   */
  protected function doTest(array $users, $email, $expected_message, $expected_user_key = FALSE) {
    // Create our users, tracking our expected user.
    $expected_user = FALSE;
    foreach ($users as $key => $values) {
      $user = $this->createUnsavedUser($values['decoupled'], $values['email_prefix']);
      $user->save();
      if ($key === $expected_user_key) {
        $expected_user = $user->id();
      }
    }

    // Check we have an expected user, if expected.
    if ($expected_user_key !== FALSE) {
      $this->assertNotEmpty($expected_user, 'Found expected user');
    }

    // Submit password reset form.
    $this->drupalGet('user/password');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $input = $page->findField('name');
    $input->setValue($email);
    $page->pressButton('Submit');

    // Check our resulting page.
    $page = $this->getSession()->getPage();
    $message = $page->find('css', '.messages');
    $this->assertNotEmpty($message, 'Message found');
    $this->assertTrue($message->hasClass($expected_user_key === FALSE ? 'messages--error' : 'messages--status'), 'Message is of correct type');
    $this->assertSame($expected_message, $message->getText(), 'Message has correct text');

    // If we have an expected user, check our email sent correctly.
    if ($expected_user) {
      $this->assertMail('to', $email, 'Password email sent to user');
      $this->assertMailString('body', "/user/reset/{$expected_user}", 1, 'Correct user in reset email');
    }
    // Otherwise there should be no email.
    else {
      $this->assertEmpty($this->container->get('state')->get('system.test_mail_collector'), 'No emails sent');
    }
  }

}
