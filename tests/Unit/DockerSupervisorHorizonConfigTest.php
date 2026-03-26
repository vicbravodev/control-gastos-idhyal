<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DockerSupervisorHorizonConfigTest extends TestCase
{
    public function test_docker_supervisor_configs_include_horizon_worker(): void
    {
        $base = dirname(__DIR__, 2).'/docker';
        $paths = glob($base.'/*/supervisord.conf');

        $this->assertNotEmpty($paths, 'Expected docker/*/supervisord.conf files.');

        foreach ($paths as $path) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents, "Could not read {$path}");
            $this->assertStringContainsString('[program:horizon]', $contents, $path);
            $this->assertStringContainsString('/var/www/html/artisan horizon', $contents, $path);
            $this->assertStringContainsString('autostart=true', $contents, $path);
            $this->assertStringContainsString('autorestart=true', $contents, $path);
            $this->assertStringContainsString('stopwaitsecs=3600', $contents, $path);
            $this->assertStringContainsString('%(ENV_SUPERVISOR_PHP_USER)s', $contents, $path);
        }
    }
}
