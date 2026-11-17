<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\OrderRepository;

final class OrderController
{
    use AdminFormTrait;

    private OrderRepository $orders;

    public function __construct(?OrderRepository $orders = null)
    {
        $this->orders = $orders ?? new OrderRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        $pageRaw = $request->query('page', '1');
        $page = is_scalar($pageRaw) ? max(1, (int) $pageRaw) : 1;
        $pagination = $this->orders->paginate($page, 20);

        $queryParams = $_GET;
        unset($queryParams['page']);

        return $this->renderAdmin('admin/orders/index', [
            'title' => 'Orders',
            'pagination' => $pagination,
            'queryParams' => $queryParams,
        ], 'orders');
    }

    public function show(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $order = $this->orders->findAdmin($id);

        if ($order === null) {
            return $this->notFound('Order not found.');
        }

        return $this->renderAdmin('admin/orders/show', [
            'title' => 'Order ' . (string) ($order['invoice_number'] ?? ''),
            'order' => $order,
            'items' => $this->orders->items($id),
        ], 'orders');
    }
}
