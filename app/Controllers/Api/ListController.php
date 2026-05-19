<?php

namespace App\Controllers\Api;

use App\Core\ActivityLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Repositories\TaskListRepository;

class ListController extends Controller
{
    private TaskListRepository $lists;

    public function __construct()
    {
        $this->lists = new TaskListRepository();
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $data = Request::all();
        $members = array_filter(array_map('trim', explode(',', (string) ($data['members'] ?? ''))));

        $result = $this->lists->create(
            Auth::id(),
            Validator::sanitizeString($data['nama_list'] ?? ''),
            $data['jenis'] ?? 'pribadi',
            $members
        );

        if (!$result['ok']) {
            $this->jsonError($result['msg']);
        }

        ActivityLogger::log(Auth::id(), 'list.created', 'list', $result['list_id'] ?? null);
        $this->jsonOk($result);
    }

    public function update(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $data = Request::all();
        $members = array_filter(array_map('trim', explode(',', (string) ($data['members'] ?? ''))));

        $result = $this->lists->update(
            Auth::id(),
            (int) ($data['list_id'] ?? 0),
            Validator::sanitizeString($data['nama_list'] ?? ''),
            $data['jenis'] ?? 'pribadi',
            $members
        );

        if (!$result['ok']) {
            $this->jsonError($result['msg']);
        }

        ActivityLogger::log(Auth::id(), 'list.updated', 'list', (int) $data['list_id']);
        $this->jsonOk($result);
    }

    public function destroy(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $result = $this->lists->softDelete(Auth::id(), (int) Request::input('list_id', 0));
        if (!$result['ok']) {
            $this->jsonError($result['msg']);
        }

        ActivityLogger::log(Auth::id(), 'list.deleted', 'list', (int) Request::input('list_id', 0));
        $this->jsonOk($result);
    }
}
