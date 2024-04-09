<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the requirements checks of the User module.
 *
 * @group user
 */
class UserRequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove and fix test to not rely on super user.
   * @see https://www.drupal.org/project/drupal/issues/3437620
   */
  protected bool $usesSuperUserAccessPolicy = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the requirements check can detect a missing anonymous user.
   */
  public function testAnonymousUser(): void {
    // Remove the anonymous user.
    \Drupal::database()
      ->delete('users')
      ->condition('uid', 0)
      ->execute();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The anonymous user does not exist.");
  }

}
