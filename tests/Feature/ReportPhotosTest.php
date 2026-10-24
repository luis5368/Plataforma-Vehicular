<?php

namespace Tests\Feature;

use App\Models\ReportPhoto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('report_photos');
    }

    private function account(string $role = 'registro'): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function payload(array $photos = []): array
    {
        return ['vin' => '1HGCM82633A000001', 'plate' => 'P101AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'theft_report_address' => 'Lugar declarado',
            'has_theft_report' => '1', 'theft_report_date' => now()->subDay()->toDateString(),
            'theft_description' => 'Descripción declarada', 'evidence_reference' => 'REF-TEST', 'photos' => $photos];
    }

    private function image(string $format = 'png'): UploadedFile
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD is required; run php -d extension=gd vendor/bin/phpunit.');
        }

        return UploadedFile::fake()->image('private.'.$format, 40, 40);
    }

    public function test_report_without_photos_remains_automatic(): void
    {
        $this->actingAs($this->account())->post('/vehiculos', $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('report_photos', 0);
        $this->assertDatabaseHas('theft_reports', ['validation_method' => 'automatica', 'case_status' => 'abierto']);
    }

    public function test_valid_images_are_reencoded_private_and_authorized_for_existing_roles(): void
    {
        $owner = $this->account();
        $this->actingAs($owner)->post('/vehiculos', $this->payload([$this->image(), $this->image('jpg'), $this->image('webp')]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('report_photos', 3);
        $this->assertDatabaseCount('photo_file_deletions', 0);
        $photo = ReportPhoto::firstOrFail();
        $this->assertSame($owner->id, $photo->uploaded_by);
        Storage::disk('report_photos')->assertExists($photo->path);
        $this->assertArrayNotHasKey('path', $photo->toArray());
        $this->assertArrayNotHasKey('disk', $photo->toArray());
        $this->get(route('reports.photos.show', $photo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/storage/'.$photo->path)->assertForbidden();
        $this->get('/storage/reports/photos/'.$photo->id)->assertForbidden();
        $this->actingAs($this->account())->get(route('reports.photos.show', $photo))->assertForbidden();
        foreach (['consulta', 'admin'] as $role) {
            $this->actingAs($this->account($role))->get(route('reports.photos.show', $photo))->assertOk();
        }
        $this->get('/consultar/buscar?vin='.$photo->theftReport->vehicle->vin)->assertOk()->assertSee(route('reports.photos.show', $photo));
        $this->post('/logout');
        $this->get(route('reports.photos.show', $photo))->assertRedirect('/login');
        $this->post('/consulta-publica', ['vin' => $photo->theftReport->vehicle->vin])->assertOk()->assertDontSee('/reportes/fotos/')->assertDontSee($photo->path);
    }

    public function test_invalid_images_counts_sizes_and_dimensions_do_not_leave_rows_or_files(): void
    {
        $this->actingAs($this->account());
        $invalid = [
            [UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
            [UploadedFile::fake()->createWithContent('bad.jpg', 'not an image')],
            [UploadedFile::fake()->createWithContent('fake.png', "\x89PNG\r\n\x1a\ninvalid")],
            [UploadedFile::fake()->create('large.jpg', 5121, 'image/jpeg')],
            array_map(fn () => $this->image(), range(1, 6)),
            [UploadedFile::fake()->image('bad.gif')],
            [UploadedFile::fake()->image('wide.png', 4097, 1)],
            [UploadedFile::fake()->image('pixels.png', 3000, 3000)],
        ];
        foreach ($invalid as $photos) {
            $this->post('/vehiculos', $this->payload($photos))->assertSessionHasErrors();
            $this->assertDatabaseCount('vehicles', 0);
            $this->assertDatabaseCount('theft_reports', 0);
            $this->assertDatabaseCount('report_photos', 0);
            $this->assertCount(0, Storage::disk('report_photos')->allFiles());
        }
    }

    public function test_database_failure_cleans_staged_files_without_sensitive_errors(): void
    {
        ReportPhoto::creating(fn () => throw new \RuntimeException('private filesystem details'));
        $this->actingAs($this->account())->post('/vehiculos', $this->payload([$this->image()]))->assertSessionHasErrors('photos');
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('report_photos', 0);
        $this->assertDatabaseCount('photo_file_deletions', 0);
        $this->assertCount(0, Storage::disk('report_photos')->allFiles());
    }

    public function test_partial_upload_failure_is_cleaned(): void
    {
        $disk = Storage::disk('report_photos');
        $mock = \Mockery::mock($disk);
        $calls = 0;
        $mock->shouldReceive('put')->andReturnUsing(function (...$args) use (&$calls, $disk) {
            return ++$calls === 1 ? $disk->put(...$args) : false;
        });
        Storage::shouldReceive('disk')->with('report_photos')->andReturn($mock);
        $this->actingAs($this->account())->post('/vehiculos', $this->payload([$this->image(), $this->image()]))->assertSessionHasErrors('photos');
        $this->assertCount(0, $disk->allFiles());
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('photo_file_deletions', 0);
    }

    public function test_account_deactivation_and_closed_case_keep_files_but_permitted_deletion_cleans_them(): void
    {
        $owner = $this->account();
        $this->actingAs($owner)->post('/vehiculos', $this->payload([$this->image()]))->assertSessionHasNoErrors();
        $photo = ReportPhoto::firstOrFail();
        $report = $photo->theftReport;
        foreach (['resuelto', 'retirado'] as $state) {
            $report->forceFill(['case_status' => $state])->save();
            $this->delete('/vehiculos/'.$report->vehicle_id)->assertForbidden();
            $this->put('/vehiculos/'.$report->vehicle_id, $this->payload([$this->image()]))->assertForbidden();
            $this->assertDatabaseCount('report_photos', 1);
            $this->assertCount(1, Storage::disk('report_photos')->allFiles());
        }
        Storage::disk('report_photos')->assertExists($photo->path);
        $this->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseHas('report_photos', ['id' => $photo->id]);
        Storage::disk('report_photos')->assertExists($photo->path);
        $this->actingAs($this->account('admin'))->delete('/vehiculos/'.$report->vehicle_id)->assertRedirect();
        Storage::disk('report_photos')->assertMissing($photo->path);
        $this->assertDatabaseCount('report_photos', 0);
    }

    public function test_owner_open_case_deletion_and_durable_failed_cleanup(): void
    {
        $this->actingAs($this->account())->post('/vehiculos', $this->payload([$this->image()]))->assertSessionHasNoErrors();
        $photo = ReportPhoto::firstOrFail();
        $disk = Storage::disk('report_photos');
        $mock = \Mockery::mock($disk);
        $canDelete = false;
        $mock->shouldReceive('delete')->andReturnUsing(function (...$args) use (&$canDelete, $disk) {
            return $canDelete ? $disk->delete(...$args) : false;
        });
        Storage::shouldReceive('disk')->with('report_photos')->andReturn($mock);
        $this->delete('/vehiculos/'.$photo->theftReport->vehicle_id)->assertRedirect();
        $this->assertDatabaseCount('report_photos', 0);
        $this->assertDatabaseCount('photo_file_deletions', 1);
        $disk->assertExists($photo->path);
        $canDelete = true;
        app(ReportPhotos::class)->cleanup();
        $disk->assertMissing($photo->path);
        $this->assertDatabaseCount('photo_file_deletions', 0);
    }

    public function test_total_photo_limit_is_enforced_on_update(): void
    {
        $this->actingAs($this->account())->post('/vehiculos', $this->payload(array_map(fn () => $this->image(), range(1, 5))))->assertSessionHasNoErrors();
        $this->put('/vehiculos/'.Vehicle::firstOrFail()->id, $this->payload([$this->image()]))->assertSessionHasErrors('photos');
        $this->assertDatabaseCount('report_photos', 5);
        $this->assertCount(5, Storage::disk('report_photos')->allFiles());
    }

    public function test_original_jpeg_metadata_and_appended_content_are_removed(): void
    {
        $bytes = $this->image('jpg')->get();
        $marker = "Exif\0\0GPS-private-fixture";
        $bytes = substr($bytes, 0, 2)."\xff\xe1".pack('n', strlen($marker) + 2).$marker.substr($bytes, 2).'appended-fixture';
        $upload = UploadedFile::fake()->createWithContent('original.jpg', $bytes);
        $this->actingAs($this->account())->post('/vehiculos', $this->payload([$upload]))->assertSessionHasNoErrors();
        $photo = ReportPhoto::firstOrFail();
        $stored = Storage::disk('report_photos')->get($photo->path);
        $this->assertFalse(str_contains($stored, 'GPS-private-fixture'));
        $this->assertFalse(str_contains($stored, 'appended-fixture'));
        $this->assertSame('image/jpeg', getimagesizefromstring($stored)['mime']);
    }

    public function test_photo_endpoint_has_its_own_frequency_limit(): void
    {
        $this->actingAs($this->account())->post('/vehiculos', $this->payload([$this->image()]))->assertSessionHasNoErrors();
        $url = route('reports.photos.show', ReportPhoto::firstOrFail());
        for ($i = 0; $i < 120; $i++) {
            $this->get($url)->assertOk();
        }
        $this->get($url)->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_server_post_limit_has_a_spanish_error(): void
    {
        $this->withServerVariables(['CONTENT_LENGTH' => 999999999])->post('/vehiculos')
            ->assertStatus(413)->assertSee('La carga es demasiado grande');
    }
}
