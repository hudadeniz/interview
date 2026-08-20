<?php

require_once __DIR__ . '/TurkpinApi.php';

class Home
{
    public function index()
    {
        global $smarty;

        $games = [];
        $products = [];
        $error = null;
        $selectedGame = $_GET['game'] ?? null;

        try {
            $api = new TurkpinApi();

            $response = $api->getGames();

            foreach ($response->params->oyunlar->oyun as $game) {
                $games[(string) $game->oyunKodu] = (string) $game->oyunAdi;
            }

            if ($selectedGame !== null && $selectedGame !== '') {
                $productResponse = $api->getProducts($selectedGame);

                foreach ($productResponse->params->urunler->urun as $product) {
                    $products[] = [
                        'id' => (string) $product->urunKodu,
                        'name' => (string) $product->urunAdi,
                        'stock' => (string) $product->stok,
                        'min_order' => (string) $product->min_order,
                        'max_order' => (string) $product->max_order,
                        'price' => (string) $product->fiyat,
                    ];
                }
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $smarty->assign('games', $games);
        $smarty->assign('products', $products);
        $smarty->assign('error', $error);
        $smarty->assign('selectedGame', $selectedGame);
        $smarty->assign('template', 'home.html');
    }

    public function createOrder()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $gameCode = $_POST['game_code'] ?? '';
            $orders = $_POST['orders'] ?? [];

            if ($gameCode === '') {
                throw new RuntimeException('Oyun seçilmedi.');
            }

            if (!is_array($orders) || count($orders) === 0) {
                throw new RuntimeException('En az bir ürün seçmelisiniz.');
            }

            $api = new TurkpinApi();
            $results = [];

            foreach ($orders as $order) {
                $productCode = $order['product_code'] ?? '';
                $quantity = (int) ($order['quantity'] ?? 0);

                if ($productCode === '') {
                    throw new RuntimeException('Ürün bilgisi eksik.');
                }

                if ($quantity < 1) {
                    throw new RuntimeException(
                        'Sipariş miktarı en az 1 olmalıdır.'
                    );
                }

                $response = $api->createOrder(
                    $gameCode,
                    $productCode,
                    $quantity
                );

                $orderNumber = (string) (
                    $response->params->SIPARIS_NO ?? ''
                );

                $results[] = [
                    'product_code' => $productCode,
                    'quantity' => $quantity,
                    'order_number' => $orderNumber
                ];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Siparişiniz başarıyla oluşturuldu.',
                'orders' => $results
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}