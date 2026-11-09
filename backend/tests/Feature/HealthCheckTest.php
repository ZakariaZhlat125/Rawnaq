<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class HealthCheckTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(base_path('RELEASE'));
        parent::tearDown();
    }

    public function test_reports_ok_and_release_when_database_is_reachable(): void
    {
        file_put_contents(base_path('RELEASE'), "20261019-120000-abc1234\n");

        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'database' => true, 'release' => '20261019-120000-abc1234']);
    }

    public function test_release_is_null_outside_a_deployed_release(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJsonPath('release', null);
    }

    public function test_returns_503_when_database_is_down(): void
    {
        config(['database.connections.broken' => ['driver' => 'sqlite', 'database' => '/nonexistent/dir/db.sqlite']]);
        DB::setDefaultConnection('broken');

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJson(['status' => 'degraded', 'database' => false]);
    }
}
