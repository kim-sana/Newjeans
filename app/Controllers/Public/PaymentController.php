<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\OrderRepository;
use App\Payments\MockPaymentGateway;
use App\Payments\PaymentGateway;
use App\Services\CheckoutService;
use RuntimeException;

final class PaymentController
{
    use PublicViewTrait;

    private OrderRepository $orders;

    private CheckoutService $service;

    private PaymentGateway $gateway;

    public function __construct(
        ?OrderRepository $orders = null,
        ?CheckoutService $service = null,
        ?PaymentGateway $gateway = null
    ) {
        $this->orders = $orders ?? new OrderRepository();
        $this->service = $service ?? new CheckoutService();
        $this->gateway = $gateway ?? new MockPaymentGateway();
    }

    public function show(Request $request, array $params = []): Response
    {
        $token = $this->validToken($params['token'] ?? null);

        if ($token === null) {
            return $this->withSecurityHeaders($this->renderNotFound('Order not found.'));
        }

        $order = $this->orders->findByToken($token);

        if (!is_array($order)) {
            return $this->withSecurityHeaders($this->renderNotFound('Order not found.'));
        }

        if ((string) ($order['status'] ?? '') === 'PAID') {
            return $this->withSecurityHeaders(Response::redirect(url('/orders/' . $token), 303));
        }

        $items = $this->orders->items((int) $order['id']);

        return $this->withSecurityHeaders($this->renderPublic('pages/payment', [
            'order' => $order,
            'items' => $items,
            'token' => $token,
        ], [
            'title' => 'Payment — ' . (string) ($order['invoice_number'] ?? 'Order') . ' — NewJeans',
            'description' => 'Complete your simulated album order payment.',
            'active' => 'cart',
            'styles' => ['css/purchase.css'],
        ]));
    }

    public function pay(Request $request, array $params = []): Response
    {
        $csrf = $request->post('_token');

        if (!is_string($csrf) || !Csrf::validate($csrf)) {
            return $this->withSecurityHeaders(Response::html('Invalid or expired security token.', 419));
        }

        $token = $this->validToken($params['token'] ?? null);

        if ($token === null) {
            return $this->withSecurityHeaders($this->renderNotFound('Order not found.'));
        }

        $order = $this->orders->findByToken($token);

        if (!is_array($order)) {
            return $this->withSecurityHeaders($this->renderNotFound('Order not found.'));
        }

        $outcomeRaw = $request->post('outcome');
        $outcome = is_string($outcomeRaw) ? trim($outcomeRaw) : '';

        if (!in_array($outcome, ['success', 'failure'], true)) {
            flash('error', 'Choose a valid payment outcome.');

            return $this->withSecurityHeaders(
                Response::redirect(url('/checkout/' . $token . '/payment'), 303)
            );
        }

        $result = $this->gateway->simulate($outcome);

        if (!$result->successful()) {
            $this->service->pay($order, $result);
            flash('error', $result->message());

            return $this->withSecurityHeaders(
                Response::redirect(url('/checkout/' . $token . '/payment'), 303)
            );
        }

        try {
            $paymentOutcome = $this->service->pay($order, $result);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());

            return $this->withSecurityHeaders(
                Response::redirect(url('/checkout/' . $token . '/payment'), 303)
            );
        }

        if ($paymentOutcome['status'] === 'paid') {
            flash('success', 'Payment confirmed. Thank you for your order!');

            return $this->withSecurityHeaders(Response::redirect(url('/orders/' . $token), 303));
        }

        if ($paymentOutcome['status'] === 'unavailable') {
            $skipped = $paymentOutcome['skipped'];
            $restored = (int) ($paymentOutcome['restored'] ?? 0);
            $parts = ['Your order was cancelled because stock changed before payment completed.'];

            if ($skipped !== []) {
                $parts[] = 'Unavailable items removed: ' . implode(', ', $skipped) . '.';
            }

            if ($restored > 0) {
                $parts[] = 'Available items were restored to your cart.';
            }

            flash('error', implode(' ', $parts));

            return $this->withSecurityHeaders(Response::redirect(url('/cart'), 303));
        }

        flash('error', 'This order was cancelled and can no longer be paid.');

        return $this->withSecurityHeaders(Response::redirect(url('/orders/' . $token), 303));
    }

    public function status(Request $request, array $params = []): Response
    {
        $token = $this->validToken($params['token'] ?? null);

        if ($token === null) {
            return $this->withSecurityHeaders($this->renderNotFound('Order not found.'));
        }

        $order = $this->orders->findByToken($token);

        if (!is_array($order)) {
            return $this->withSecurityHeaders($this->renderNotFound('Order not found.'));
        }

        $items = $this->orders->items((int) $order['id']);

        return $this->withSecurityHeaders($this->renderPublic('pages/order-status', [
            'order' => $order,
            'items' => $items,
            'token' => $token,
        ], [
            'title' => 'Order ' . (string) ($order['invoice_number'] ?? '') . ' — NewJeans',
            'description' => 'Order summary and payment status.',
            'active' => 'cart',
            'styles' => ['css/purchase.css'],
        ]));
    }

    private function validToken(mixed $token): ?string
    {
        if (!is_string($token)) {
            return null;
        }

        $token = trim($token);

        return preg_match('/^[a-f0-9]{64}$/', $token) === 1 ? $token : null;
    }

    private function renderNotFound(string $message): Response
    {
        $content = View::render('pages/404', ['message' => $message]);

        return Response::html(View::layout('layouts/public', $content, [
            'title' => 'Order not found — NewJeans',
            'description' => 'The requested order could not be found.',
            'active' => '',
            'styles' => [],
            'scripts' => [],
            'bodyClass' => '',
        ]), 404);
    }

    private function withSecurityHeaders(Response $response): Response
    {
        return $response
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('X-Robots-Tag', 'noindex')
            ->withHeader('Cache-Control', 'no-store');
    }
}
