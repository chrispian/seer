<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Inbox\InboxService;

class InboxController extends Controller
{
    public function __construct(protected InboxService $service) {}

    public function index(Request $r)
    {
        $q = [
            'status' => $r->query('status','pending'),
            'type' => $r->query('type'),
            'tag' => $r->query('tag'),
            'category' => $r->query('category'),
            'vault' => $r->query('vault'),
            'q' => $r->query('q'),
            'sort' => $r->query('sort','inbox_at'),
            'order' => $r->query('order','desc'),
            'limit' => min(200, (int) $r->query('limit', 50)),
            'cursor' => $r->query('cursor'),
        ];
        return response()->json($this->service->list($r->user(), $q));
    }

    public function accept(Request $r)
    {
        $ids = (array) $r->input('ids', []);
        $edits = (array) $r->input('edits', []);
        $note = (string) $r->input('note', '');
        $ai = (array) $r->input('ai', []); // override env toggles per-request if needed

        $count = $this->service->accept($r->user(), $ids, $edits, $note, $ai);
        return response()->json(['accepted' => $count]);
    }

    public function acceptAll(Request $r)
    {
        $filter = (array) $r->input('filter', []);
        $edits = (array) $r->input('edits', []);
        $note = (string) $r->input('note', '');
        $ai = (array) $r->input('ai', []);

        $count = $this->service->acceptAll($r->user(), $filter, $edits, $note, $ai);
        return response()->json(['accepted' => $count]);
    }

    public function archive(Request $r)
    {
        $ids = (array) $r->input('ids', []);
        $note = (string) $r->input('note', '');
        $count = $this->service->archive($r->user(), $ids, $note);
        return response()->json(['archived' => $count]);
    }

    public function reopen(Request $r)
    {
        $ids = (array) $r->input('ids', []);
        $count = $this->service->reopen($r->user(), $ids);
        return response()->json(['reopened' => $count]);
    }

    public function tag(Request $r)
    {
        $id = $r->input('id');
        $add = (array) $r->input('add', []);
        $remove = (array) $r->input('remove', []);
        $res = $this->service->tag($r->user(), $id, $add, $remove);
        return response()->json($res);
    }
}
