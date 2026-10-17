<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Services\OddsFetcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshController extends Controller
{
    public function __invoke(Request $request, OddsFetcher $fetcher): JsonResponse
    {
        $count = $fetcher->fetch(force: true);
        AdminActionLog::create([
            'user_id' => $request->user()->id,
            'action' => 'refresh',
            'table_cible' => 'odds',
            'nouvelles_valeurs' => ['cotes_synchronisees' => $count],
            'raison' => $request->input('raison'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['cotes_synchronisees' => $count]);
    }
}