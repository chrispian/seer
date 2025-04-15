<?php

    namespace App\Http\Controllers;

    use App\Models\Fragment;
    use Illuminate\Http\Request;

    class FragmentController extends Controller
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

            $log = \App\Models\Fragment::create($data);

            return response()->json($log);
        }

        public function update(Request $request, Fragment $fragment)
        {
            $fragment->update($request->only(['type', 'message', 'tags', 'relationships']));
            return response()->json($fragment);
        }

        public function index(Request $request)
        {
            return response()->json(
                Fragment::query()
                    ->latest()
                    ->get()
            );
        }

        public function search(Request $request)
        {
            $query = $request->get('q');

            return response()->json(
                \App\Models\Fragment::with('category')
                    ->where('message', 'like', "%{$query}%")
                    ->orWhereJsonContains('tags', $query)
                    ->latest()
                    ->limit(10)
                    ->get()
            );
        }

        public function recall(Request $request)
        {
            $query = Fragment::with('category')->latest();

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $limit = $request->get('limit', 5);

            return response()->json(
                $query->take($limit)->get()
            );
        }


    }
