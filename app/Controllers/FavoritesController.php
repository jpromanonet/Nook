<?php

declare(strict_types=1);

final class FavoritesController
{
    public static function index(): void
    {
        Auth::requireLogin();
        $items = ItemService::list([
            'favorite' => true,
            'order' => 'i.updated_at DESC',
            'limit' => 100,
        ]);
        view('favorites/index', [
            'title' => 'Favorites',
            'items' => $items,
        ]);
    }
}
