<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $service) {}

    public function store(CreateTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', Transaction::class);

        $transaction = $this->service->create(
            vendor: $request->user(),
            data: $request->validated(),
        );

        return response()->json([
            'message' => 'Transaction créée avec succès.',
            'data' => new TransactionResource($transaction->load('vendor')),
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $transaction = Transaction::where('secure_token', $token)
            ->with(['vendor', 'buyer'])
            ->firstOrFail();

        return response()->json([
            'data' => new TransactionResource($transaction),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('listOwn', Transaction::class);

        $transactions = Transaction::with(['vendor', 'buyer'])
            ->where(function ($query) use ($request) {
                $query->where('vendor_id', $request->user()->id)
                    ->orWhere('buyer_id', $request->user()->id);
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => TransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function cancel(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('cancel', $transaction);

        $transaction = $this->service->cancel($transaction);

        return response()->json([
            'message' => 'Transaction annulée.',
            'data' => new TransactionResource($transaction->load('vendor')),
        ]);
    }
}
