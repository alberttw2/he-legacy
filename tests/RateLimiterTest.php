<?php

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    public function testAllowsWithinLimit()
    {
        $this->assertTrue(RateLimiter::check('test_allow', 5, 60, '1.2.3.4'));
        RateLimiter::reset('test_allow', '1.2.3.4');
    }

    public function testBlocksOverLimit()
    {
        $ip = '9.8.7.6';
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::check('test_block', 3, 60, $ip);
        }
        $this->assertFalse(RateLimiter::check('test_block', 3, 60, $ip));
        RateLimiter::reset('test_block', $ip);
    }

    public function testResetClearsLimit()
    {
        $ip = '5.5.5.5';
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check('test_reset', 5, 60, $ip);
        }
        $this->assertFalse(RateLimiter::check('test_reset', 5, 60, $ip));
        RateLimiter::reset('test_reset', $ip);
        $this->assertTrue(RateLimiter::check('test_reset', 5, 60, $ip));
        RateLimiter::reset('test_reset', $ip);
    }

    public function testDifferentActionsAreIndependent()
    {
        $ip = '2.2.2.2';
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::check('action_a', 3, 60, $ip);
        }
        $this->assertFalse(RateLimiter::check('action_a', 3, 60, $ip));
        $this->assertTrue(RateLimiter::check('action_b', 3, 60, $ip));
        RateLimiter::reset('action_a', $ip);
        RateLimiter::reset('action_b', $ip);
    }
}
