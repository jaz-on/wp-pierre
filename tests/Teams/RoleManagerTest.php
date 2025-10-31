<?php

use PHPUnit\Framework\TestCase;
use Pierre\Teams\RoleManager;

// Minimal WP stubs for this test scope
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['__pierre_filters'][$hook][] = [$callback, $accepted_args];
        return true;
    }
}

if (!function_exists('get_role')) {
    class __Pierre_TestRole {
        public array $caps = [];
        public function has_cap($cap) { return !empty($this->caps[$cap]); }
        public function add_cap($cap) { $this->caps[$cap] = true; }
    }
    function get_role($name) {
        static $role; if (!$role) { $role = new __Pierre_TestRole(); }
        return $role;
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        return (object) [
            'ID' => (int) $user_id,
            'roles' => ['administrator'],
            'allcaps' => ['manage_options' => true]
        ];
    }
}

final class RoleManagerTest extends TestCase {

    public function test_add_capabilities_grants_caps_to_admin_role(): void {
        $rm = new RoleManager();
        $rm->add_capabilities();
        $admin = get_role('administrator');
        $this->assertTrue($admin->has_cap('pierre_view_dashboard'));
        $this->assertTrue($admin->has_cap('pierre_manage_settings'));
    }

    public function test_user_has_cap_filter_grants_all_caps_when_administrator_present(): void {
        $rm = new RoleManager();
        $rm->add_capabilities();
        $filters = $GLOBALS['__pierre_filters']['user_has_cap'] ?? [];
        $this->assertNotEmpty($filters);
        [$cb, $args] = $filters[0];
        $allcaps = ['administrator' => true];
        $out = call_user_func($cb, $allcaps, [], [], (object)[]);
        $this->assertArrayHasKey('pierre_manage_projects', $out);
        $this->assertTrue($out['pierre_manage_projects']);
    }

    public function test_map_meta_cap_short_circuits_for_administrator(): void {
        $rm = new RoleManager();
        $rm->add_capabilities();
        $filters = $GLOBALS['__pierre_filters']['map_meta_cap'] ?? [];
        $this->assertNotEmpty($filters);
        [$cb, $args] = $filters[0];
        $required = ['do_not_allow'];
        $mapped = call_user_func($cb, $required, 'pierre_manage_locale', 1, ['locale' => 'fr']);
        $this->assertSame(['exist'], $mapped);
    }
}


