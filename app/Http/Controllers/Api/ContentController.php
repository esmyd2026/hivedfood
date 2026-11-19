<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteContentService;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function __construct(private SiteContentService $content) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->content->homePayload(),
        ]);
    }

    public function quoter(): JsonResponse
    {
        return response()->json([
            'data' => $this->content->quoterConfig(),
        ]);
    }
}
