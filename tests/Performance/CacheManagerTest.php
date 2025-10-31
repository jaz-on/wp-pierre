<?php

use PHPUnit\Framework\TestCase;
use Pierre\Performance\CacheManager;

// Provide object cache mocks for this test file
if (!function_exists('wp_using_ext_object_cache')) {
    function wp_using_ext_object_cache() { return true; }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null) {
        global $__test_object_cache; if (!is_array($__test_object_cache ?? null)) { $__test_object_cache = []; }
        $k = $group . ':' . $key;
        if (array_key_exists($k, $__test_object_cache)) { $found = true; return $__test_object_cache[$k]; }
        $found = false; return false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0) {
        global $__test_object_cache; if (!is_array($__test_object_cache ?? null)) { $__test_object_cache = []; }
        $__test_object_cache[$group . ':' . $key] = $data;
        return true;
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '') {
        global $__test_object_cache; if (!is_array($__test_object_cache ?? null)) { $__test_object_cache = []; }
        $k = $group . ':' . $key;
        if (array_key_exists($k, $__test_object_cache)) { unset($__test_object_cache[$k]); return true; }
        return false;
    }
}

final class CacheManagerTest extends TestCase {

    public function test_set_and_get_with_object_cache(): void {
        $cm = new CacheManager();
        $this->assertTrue($cm->set('foo', ['bar' => 1], 600));
        $v = $cm->get('foo');
        $this->assertIsArray($v);
        $this->assertSame(1, $v['bar']);
    }

    public function test_delete_removes_entry(): void {
        $cm = new CacheManager();
        $cm->set('tmp', 'x', 600);
        $this->assertSame('x', $cm->get('tmp'));
        $this->assertTrue($cm->delete('tmp'));
        $this->assertFalse($cm->get('tmp'));
    }

    public function test_remember_caches_callback_result(): void {
        $cm = new CacheManager();
        $calls = 0;
        $cb = function () use (&$calls) { $calls++; return 'value'; };
        $v1 = $cm->remember('remember_key', $cb, 600);
        $v2 = $cm->remember('remember_key', $cb, 600);
        $this->assertSame('value', $v1);
        $this->assertSame('value', $v2);
        $this->assertSame(1, $calls, 'Callback should be called once');
    }
}


