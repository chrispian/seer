<?php

namespace Database\Factories;

use App\Models\OrchestrationArtifact;
use App\Models\WorkItem;
use App\Services\Orchestration\Artifacts\ContentStore;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrchestrationArtifactFactory extends Factory
{
    protected $model = OrchestrationArtifact::class;

    public function definition(): array
    {
        $content = $this->faker->text(200);
        $hash = hash('sha256', $content);
        $filename = $this->faker->word().'.txt';
        $store = new ContentStore;

        $store->put($content);

        return [
            'task_id' => WorkItem::factory(),
            'hash' => $hash,
            'filename' => $filename,
            'mime_type' => 'text/plain',
            'size_bytes' => strlen($content),
            'fe_uri' => $store->formatUri($hash, 'TEST', $filename),
            'storage_path' => $store->getHashPath($hash),
        ];
    }
}
