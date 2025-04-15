<?php

    namespace App\Http\Controllers;

    use App\Models\SeerLog;
    use Illuminate\Http\Request;

    class SeerLogController extends Controller
    {
        public function store(Request $request)
        {
            $data = $request->validate([
                'type' => 'required|string',
                'message' => 'required|string',
                'tags' => 'nullable|array',
                'relationships' => 'nullable|array',
                'category' => 'nullable|string',
            ]);

            // Find or create category
            if (!empty($data['category'])) {
                $category = \App\Models\Category::firstOrCreate(['name' => $data['category']]);
                $data['category_id'] = $category->id;
            }

            unset($data['category']); // prevent mass-assignment issues

            $log = \App\Models\SeerLog::create($data);

            return response()->json($log);
        }

        public function update(Request $request, SeerLog $log)
        {
            $log->update($request->only(['type', 'message', 'tags', 'relationships']));
            return response()->json($log);
        }

        public function index(Request $request)
        {
            return response()->json(
                SeerLog::query()
                    ->latest()
                    ->get()
            );
        }

        public function search(Request $request)
        {
            $query = $request->get('q');

            return response()->json(
                \App\Models\SeerLog::with('category')
                    ->where('message', 'like', "%{$query}%")
                    ->orWhereJsonContains('tags', $query)
                    ->latest()
                    ->limit(10)
                    ->get()
            );
        }

        public function recall(Request $request)
        {
            $query = SeerLog::with('category')->latest();

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $limit = $request->get('limit', 5);

            return response()->json(
                $query->take($limit)->get()
            );
        }


    }
