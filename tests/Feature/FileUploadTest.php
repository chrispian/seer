<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('file upload success with valid image', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100)->size(1024);
    
    $response = $this->postJson('/api/files', [
        'file' => $file,
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'file_id',
            'original_name',
            'url',
            'markdown',
            'mime_type',
            'size',
        ])
        ->assertJson([
            'success' => true,
            'original_name' => 'test.jpg',
        ]);
    
    $data = $response->json();
    expect($data['markdown'])->toStartWith('![test.jpg]');
    
    Storage::disk('public')->assertExists('uploads/' . $data['file_id']);
});

test('file upload success with PDF document', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
    
    $response = $this->postJson('/api/files', [
        'file' => $file,
    ]);
    
    $response->assertStatus(200);
    
    $data = $response->json();
    expect($data['markdown'])->toStartWith('[📄 document.pdf]');
});

test('file upload validation rejects oversized files', function () {
    // Create a file larger than 10MB
    $file = UploadedFile::fake()->create('large.jpg', 11 * 1024)->mimeType('image/jpeg');
    
    $response = $this->postJson('/api/files', [
        'file' => $file,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('file upload validation rejects invalid file types', function () {
    $file = UploadedFile::fake()->create('malicious.exe', 1024, 'application/x-executable');
    
    $response = $this->postJson('/api/files', [
        'file' => $file,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('file upload requires file parameter', function () {
    $response = $this->postJson('/api/files', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});