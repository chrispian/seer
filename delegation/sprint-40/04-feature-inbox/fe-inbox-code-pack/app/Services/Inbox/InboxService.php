<?php

namespace App\Services\Inbox;

use Illuminate\Support\Facades\DB;
use App\Services\Inbox\InboxAiAssist;
use App\Events\FragmentAccepted;
use App\Events\FragmentArchived;

class InboxService
{
    public function __construct(protected InboxAiAssist $ai) {}

    public function list($user, array $q): array
    {
        $builder = DB::table('fragments')
            ->select('id','type','tags','category','vault','title','content','state','inbox_status','inbox_at','created_at','captured_at')
            ->where('inbox_status', $q['status'] ?? 'pending');

        if ($q['type'] ?? null) $builder->where('type', $q['type']);
        if ($q['category'] ?? null) $builder->where('category', $q['category']);
        if ($q['vault'] ?? null) $builder->where('vault', $q['vault']);
        if ($q['tag'] ?? null) $builder->whereJsonContains('tags', $q['tag']);
        if ($q['q'] ?? null) $builder->where(function($w) use($q){
            $w->where('title', 'ilike', '%'.$q['q'].'%')
              ->orWhere('content','ilike','%'.$q['q'].'%');
        });

        $builder->orderBy($q['sort'] ?? 'inbox_at', $q['order'] ?? 'desc')
                ->limit($q['limit'] ?? 50);

        $items = $builder->get()->map(function($r){
            return (array) $r;
        })->all();

        return ['items' => $items, 'next' => null];
    }

    public function accept($user, array $ids, array $edits, string $note = '', array $ai = []): int
    {
        if (empty($ids)) return 0;
        $count = 0;
        foreach ($ids as $id) {
            $count += $this->acceptOne($user, $id, $edits, $note, $ai) ? 1 : 0;
        }
        return $count;
    }

    public function acceptAll($user, array $filter, array $edits, string $note = '', array $ai = []): int
    {
        // Re-run filter server-side to get ids
        $q = array_merge(['status' => 'pending'], $filter);
        $ids = array_map(fn($r)=>$r['id'], $this->list($user, $q)['items']);
        return $this->accept($user, $ids, $edits, $note, $ai);
    }

    protected function acceptOne($user, string $id, array $edits, string $note, array $ai): bool
    {
        return DB::transaction(function () use ($user, $id, $edits, $note, $ai) {
            $frag = DB::table('fragments')->lockForUpdate()->where('id',$id)->first();
            if (!$frag || $frag->inbox_status !== 'pending') return false;

            $updates = $this->applyEdits((array)$frag, $edits);

            // AI assists
            $assist = $this->ai->maybeAssist((array)$frag, $updates, $ai);
            $updates = array_merge($updates, $assist);

            $updates['inbox_status'] = 'accepted';
            $updates['reviewed_at'] = now();
            $updates['reviewed_by'] = $user->id ?? null;

            DB::table('fragments')->where('id',$id)->update($updates);

            event(new FragmentAccepted($id, $updates, $user->id ?? null));
            return true;
        });
    }

    public function archive($user, array $ids, string $note=''): int
    {
        if (empty($ids)) return 0;
        $n = DB::table('fragments')->whereIn('id', $ids)
            ->where('inbox_status','pending')
            ->update(['inbox_status'=>'archived','reviewed_at'=>now(),'reviewed_by'=>$user->id ?? null]);
        if ($n > 0) event(new FragmentArchived($ids, $user->id ?? null));
        return $n;
    }

    public function reopen($user, array $ids): int
    {
        return DB::table('fragments')->whereIn('id',$ids)->update(['inbox_status'=>'pending','reviewed_at'=>null,'reviewed_by'=>null]);
    }

    public function tag($user, string $id, array $add, array $remove): array
    {
        $frag = (array) DB::table('fragments')->where('id',$id)->first();
        $tags = json_decode($frag['tags'] ?? '[]', true) ?: [];
        $tags = array_values(array_unique(array_diff(array_merge($tags, $add), $remove)));
        DB::table('fragments')->where('id',$id)->update(['tags' => json_encode($tags)]);
        return ['id'=>$id,'tags'=>$tags];
    }

    protected function applyEdits(array $frag, array $edits): array
    {
        $u = [];
        foreach (['tags','category','type','vault','title','content','state'] as $k) {
            if (array_key_exists($k, $edits)) {
                $u[$k] = is_array($edits[$k]) ? json_encode($edits[$k]) : $edits[$k];
            }
        }
        // clear edited_message if present
        if (!empty($frag['edited_message'])) $u['edited_message'] = null;
        return $u;
    }
}
