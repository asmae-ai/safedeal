<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $service,
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

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

    public function checkout(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('checkout', $transaction);

        $frontendUrl = config('app.frontend_url');

        $successUrl = $frontendUrl.'/transactions/'.$transaction->secure_token.'?payment=success';
        $cancelUrl = $frontendUrl.'/transactions/'.$transaction->secure_token.'?payment=cancelled';

        $session = $this->paymentGateway->createCheckoutSession(
            $transaction,
            $successUrl,
            $cancelUrl,
        );

        return response()->json([
            'checkout_url' => $session['url'],
            'session_id' => $session['id'],
        ]);
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

    /**
     * Permet à un acheteur de réclamer une transaction
     * via son secure_token.
     */
    public function claim(Request $request, string $token): JsonResponse
    {
        $transaction = Transaction::where('secure_token', $token)
            ->firstOrFail();

        $transaction = $this->service->claim(
            $transaction,
            $request->user()
        );

        return response()->json([
            'message' => 'Transaction réclamée avec succès.',
            'data' => new TransactionResource(
                $transaction->load('vendor', 'buyer')
            ),
        ]);
    }

    public function pay(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->service->transitionTo(
            $transaction,
            TransactionStatus::PaymentReceived
        );

        return response()->json([
            'data' => new TransactionResource($transaction->load('vendor', 'buyer')),
        ]);
    }

    public function ship(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->service->transitionTo(
            $transaction,
            TransactionStatus::InShipping
        );

        return response()->json([
            'data' => new TransactionResource($transaction->load('vendor', 'buyer')),
        ]);
    }

    public function deliver(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->service->transitionTo(
            $transaction,
            TransactionStatus::Delivered
        );

        return response()->json([
            'data' => new TransactionResource($transaction->load('vendor', 'buyer')),
        ]);
    }

    public function close(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->service->transitionTo(
            $transaction,
            TransactionStatus::Closed
        );

        return response()->json([
            'data' => new TransactionResource($transaction->load('vendor', 'buyer')),
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