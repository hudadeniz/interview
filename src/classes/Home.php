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
}