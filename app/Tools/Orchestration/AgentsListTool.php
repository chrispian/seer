<?php

namespace App\Tools\Orchestration;

use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use App\Tools\Orchestration\Concerns\NormalisesFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AgentsListTool extends Tool implements SummarizesTool
{
    use NormalisesFilters;

    protected string $name = 'orchestration_agents_list';

    protected string $title = 'List orchestration agents';

    protected string $description = 'Return orchestration agent profiles with optional filters and summaries.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->array()->items($schema->string()->enum(['active', 'inactive', 'archived'])),
            'type' => $schema->array()->items($schema->string()),
            'mode' => $schema->array()->items($schema->string()),
            'search' => $schema->string(),
            'limit' => $schema->integer()->min(1)->max(100)->default(20),
            'include' => $schema->array()->items($schema->string()->enum(['capabilities', 'constraints', 'tools'])),
        ];
    }

    public function handle(Request $request): Response
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = ModelResolver::resolve('agent_model', class_exists('App\\Models\\AgentProfile') ? 'App\\Models\\AgentProfile' : null);

        /** @var Builder $query */
        $query = $model::query()->orderBy('name');

        if ($request->get('status')) {
            $query->whereIn('status', $this->normaliseLowercaseArray($request->get('status')));
        }

        if ($request->get('type')) {
            $query->whereIn('type', $this->normaliseLowercaseArray($request->get('type')));
        }

        if ($request->get('mode')) {
            $query->whereIn('mode', $this->normaliseLowercaseArray($request->get('mode')));
        }

        if ($request->get('search')) {
            $term = '%'.trim((string) $request->get('search')).'%';
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $limit = $this->normalisePositiveInt($request->get('limit'), 20) ?? 20;
        $query->limit($limit);

        /** @var Collection<int, \Illuminate\Database\Eloquent\Model> $agents */
        $agents = $query->get();

        $include = $this->normaliseArray($request->get('include', []));
        $includeLists = array_flip($include);

        $data = $agents->map(function ($agent) use ($includeLists) {
            $payload = [
                'id' => $agent->getKey(),
                'name' => $agent->name,
                'slug' => $agent->slug,
                'type' => $agent->type,
                'mode' => $agent->mode,
                'status' => $agent->status,
                'updated_at' => $this->optionalIso($agent->updated_at),
                'updated_human' => $this->optionalHuman($agent->updated_at),
            ];

            if (isset($includeLists['capabilities'])) {
                $payload['capabilities'] = $agent->capabilities ?? [];
            }

            if (isset($includeLists['constraints'])) {
                $payload['constraints'] = $agent->constraints ?? [];
            }

            if (isset($includeLists['tools'])) {
                $payload['tools'] = $agent->tools ?? [];
            }

            return $payload;
        });

        return Response::json([
            'data' => $data,
            'meta' => [
                'count' => $data->count(),
            ],
        ]);
    }

    public static function summaryName(): string
    {
        return 'orchestration_agents_list';
    }

    public static function summaryTitle(): string
    {
        return 'List orchestration agents';
    }

    public static function summaryDescription(): string
    {
        return 'Filter agents by status, type, mode, or search term.';
    }

    public static function schemaSummary(): array
    {
        return [
            'status[]' => 'active|inactive|archived',
            'type[]' => 'agent type slugs',
            'mode[]' => 'agent mode slugs',
            'limit' => 'defaults to 20',
        ];
    }
}
