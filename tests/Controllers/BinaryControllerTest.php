<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

// DownloadBinaryController
it('returns 404 when no binary has been uploaded', function () {
    $this->get(route('perry.download'))->assertNotFound();
});

it('streams the binary when it exists', function () {
    Storage::disk('local')->put('perry', 'binary-content');

    $this->get(route('perry.download'))->assertOk();
});

// EditBinaryController
it('redirects guests from the binary settings page', function () {
    $this->get(route('binary.edit'))->assertRedirect(route('login'));
});

it('shows the binary settings page to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('binary.edit'))
        ->assertOk();
});

it('shows binary metadata when the binary exists', function () {
    Storage::disk('local')->put('perry', 'binary-content');
    Storage::disk('local')->put('perry.sha256', hash('sha256', 'binary-content'));

    $this->actingAs(User::factory()->create())
        ->get(route('binary.edit'))
        ->assertOk();
});

// UploadBinaryController
it('uploads a binary file', function () {
    $this->actingAs(User::factory()->create());
    $file = UploadedFile::fake()->create('perry', 100, 'application/octet-stream');

    $this->post(route('binary.upload'), ['binary' => $file])
        ->assertRedirect(route('binary.edit'));

    Storage::disk('local')->assertExists('perry');
    Storage::disk('local')->assertExists('perry.sha256');
});

it('rejects uploads without a file', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('binary.upload'), [])->assertSessionHasErrors('binary');
});

// ApiUploadBinaryController
it('rejects API uploads without a bearer token', function () {
    $file = UploadedFile::fake()->create('perry', 100);

    $this->postJson('/api/binary', ['binary' => $file])
        ->assertUnauthorized();
});

it('rejects API uploads with an incorrect bearer token', function () {
    Config::set('perry.binary_upload_token', 'correct-token');
    $file = UploadedFile::fake()->create('perry', 100);

    $this->withToken('wrong-token')
        ->postJson('/api/binary', ['binary' => $file])
        ->assertUnauthorized();
});

it('accepts API uploads with the correct bearer token', function () {
    Config::set('perry.binary_upload_token', 'correct-token');
    $file = UploadedFile::fake()->create('perry', 100, 'application/octet-stream');

    $response = $this->withToken('correct-token')
        ->post('/api/binary', ['binary' => $file]);

    $response->assertOk();
    $response->assertJsonStructure(['hash']);
    Storage::disk('local')->assertExists('perry');
});
