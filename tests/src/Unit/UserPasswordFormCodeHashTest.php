<?php

namespace Drupal\Tests\decoupled_auth\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\user\Form\UserPasswordForm;

/**
 * Test to verify the password form handlers haven't changed upstream.
 *
 * @group decoupled_auth
 */
class UserPasswordFormCodeHashTest extends UnitTestCase {

  /**
   * Test that the core handlers match the expected.
   */
  public function testCore() {
    $this->assertCodeMatches('d7552132e125c59d5329a41e5544a368', [UserPasswordForm::class, 'validateForm'], 'Core validation handler matches');
  }

  /**
   * Test that the core handlers match the expected.
   */
  public function testUserRegistrationPassword() {
    // Find user_registrationpassword.module - assume it will be next to
    // decoupled_auth.
    include __DIR__ . '/../../../../user_registrationpassword/user_registrationpassword.module';
    print_r($_ENV);
    $this->assertCodeMatches('094c38cbafc20a54c8131a31d79ef1a5', '_user_registrationpassword_user_pass_validate', 'Core validation handler matches');
  }

  /**
   * Verify the code for a particular callable matches the expected via a hash.
   *
   * @param string $expected_hash
   *   The expected hash of the code.
   * @param mixed $callable
   *   The callable we are checking.
   * @param string $message
   *   The message of the assertion.
   */
  protected function assertCodeMatches($expected_hash, $callable, $message = '') {
    if (is_array($callable)) {
      $callable = new \ReflectionMethod($callable[0], $callable[1]);
    }
    else {
      $callable = new \ReflectionFunction($callable);
    }

    $start = $callable->getStartLine() - 1;
    $length = $callable->getEndLine() + 1 - $start;

    $source = file($callable->getFileName());
    $this->assertNotEmpty($source, 'Read source file');

    $body = implode('', array_slice($source, $start, $length));
    $this->assertNotEmpty($body, 'Read callable source');

    $this->assertSame($expected_hash, md5($body), $message);
  }

}
