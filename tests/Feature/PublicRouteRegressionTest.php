<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicRouteRegressionTest extends TestCase
{
    public function test_public_routes_have_stable_names_paths_and_methods(): void
    {
        $routes = [
            'home' => '/',
            'privacy' => '/privacy',
            'terms' => '/terms',
            'seo.robots' => '/robots.txt',
            'seo.sitemap' => '/sitemap.xml',
        ];

        foreach ($routes as $name => $path) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is missing.");
            $this->assertSame($path === '/' ? '/' : ltrim($path, '/'), $route->uri());
            $this->assertContains('GET', $route->methods());
        }
    }

    public function test_public_inertia_components_exist_on_disk(): void
    {
        foreach ([
            resource_path('js/pages/Welcome.vue'),
            resource_path('js/pages/Legal/Privacy.vue'),
            resource_path('js/pages/Legal/Terms.vue'),
            resource_path('js/layouts/public/PublicLayout.vue'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    public function test_public_routes_remain_route_cache_safe(): void
    {
        $this->assertSame(0, Artisan::call('route:cache'));
        $this->assertSame(0, Artisan::call('optimize:clear'));
    }
}
