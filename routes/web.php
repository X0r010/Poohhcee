<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController, OrderController, InventoryController, FinanceController,
    CollectionController, FilmCollectionController, SettingsController,
    ShirtTypeController, AuthController
};

// ── Auth ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    
    // Throttle login attempts to 5 per minute to prevent brute-force attacks
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Protected Routes ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/rows', [OrderController::class, 'rows'])->name('rows');
        Route::get('/pipeline', [OrderController::class, 'pipeline'])->name('pipeline');
        Route::get('/buylist', [OrderController::class, 'buylist'])->name('buylist');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::match(['post', 'patch'], '/{order}/status', [OrderController::class, 'updateStatus'])->name('status');
        Route::post('/{order}/advance-print', [OrderController::class, 'advancePrint'])->name('advance-print');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        
        // API helpers for dynamic frontend dropdowns
        Route::get('/api/check-inventory', [OrderController::class, 'checkInventory'])->name('api.check-inventory');
    });

    // Inventory (Shirts, Films, Printed Items)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // Shirts
        Route::get('/shirts', [InventoryController::class, 'shirts'])->name('shirts');
        Route::post('/shirts', [InventoryController::class, 'addShirt'])->name('shirts.add');
        Route::put('/shirts/{shirt}', [InventoryController::class, 'updateShirt'])->name('shirts.update');
        Route::delete('/shirts/{shirt}', [InventoryController::class, 'deleteShirt'])->name('shirts.delete');
        Route::post('/shirts/{shirt}/remove-unit', [InventoryController::class, 'removeShirtUnit'])->name('shirts.removeUnit');

        // Films
        Route::get('/films', [InventoryController::class, 'films'])->name('films');
        Route::post('/films', [InventoryController::class, 'addFilm'])->name('films.add');
        Route::put('/films/{film}', [InventoryController::class, 'updateFilm'])->name('films.update');
        Route::delete('/films/{film}', [InventoryController::class, 'deleteFilm'])->name('films.delete');
        Route::post('/films/{film}/remove-unit', [InventoryController::class, 'removeFilmUnit'])->name('films.removeUnit');

        // Printed Shirts
        Route::delete('/printed-shirts/{printedShirt}', [InventoryController::class, 'deletePrintedShirt'])->name('printed-shirts.delete');
    });

    // Finance & Expenses
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::post('/expenses', [FinanceController::class, 'addExpense'])->name('expenses.add');
        Route::delete('/expenses/{expense}', [FinanceController::class, 'deleteExpense'])->name('expenses.delete');
    });

    // Collections & Designs Management
    Route::resource('collections', CollectionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/collections/{collection}/designs', [CollectionController::class, 'addDesign'])->name('collections.designs.add');
    Route::put('/designs/{design}', [CollectionController::class, 'updateDesign'])->name('designs.update');
    Route::delete('/designs/{design}', [CollectionController::class, 'destroyDesign'])->name('designs.destroy'); // Added missing delete route
    
    // API endpoint for frontend cascading selection
    Route::get('/api/collections/{collection}/designs', [CollectionController::class, 'getDesigns'])->name('api.collections.designs');

    // Film Collections & Film Names
    Route::post('/film-collections', [FilmCollectionController::class, 'store'])->name('film.collections.store');
    Route::delete('/film-collections/{filmCollection}', [FilmCollectionController::class, 'destroy'])->name('film.collections.destroy');
    Route::post('/film-collections/{filmCollection}/films', [FilmCollectionController::class, 'addFilmName'])->name('film.collections.names.add');
    Route::delete('/film-names/{filmName}', [FilmCollectionController::class, 'destroyFilmName'])->name('film.names.destroy');

    // Shirt Types Management
    Route::post('/shirt-types', [ShirtTypeController::class, 'store'])->name('shirt-types.store');
    Route::put('/shirt-types/{shirtType}', [ShirtTypeController::class, 'update'])->name('shirt-types.update'); // Added missing update route
    Route::delete('/shirt-types/{shirtType}', [ShirtTypeController::class, 'destroy'])->name('shirt-types.destroy');

    // Shirt Colors Management
    Route::post('/shirt-colors', [InventoryController::class, 'storeColor'])->name('shirt-colors.store');
    Route::delete('/shirt-colors/{color}', [InventoryController::class, 'destroyColor'])->name('shirt-colors.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

});