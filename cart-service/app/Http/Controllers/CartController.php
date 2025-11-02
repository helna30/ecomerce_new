<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    private $client;

    public function __construct()
    {
        // Gunakan PRODUCT_SERVICE_URL dari .env, default fallback ke container service
        $baseUri = env('PRODUCT_SERVICE_URL', 'http://product-service:3000');
        $this->client = new Client(['base_uri' => $baseUri, 'timeout' => 5.0]);
    }

    /**
     * Ambil produk dari product-service
     */
    public function getProduct($productId)
    {
        try {
            $url = "/products/{$productId}";
            $response = $this->client->get($url);
            $responseData = json_decode($response->getBody()->getContents(), true);

            // Pastikan data ada dan tidak kosong
            if (!empty($responseData) && isset($responseData['data']) && !empty($responseData['data'])) {
                return $responseData['data'];
            }

            Log::warning('Invalid response from product-service', ['response' => $responseData]);
            return null;

        } catch (\Throwable $th) {
            Log::error('Error connecting to product-service', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return null;
        }
    }

    /**
     * Ambil semua cart items
     */
    public function index()
    {
        try {
            $cartItems = Cart::orderBy('created_at', 'desc')->get();
            return ResponseHelper::successResponse('Cart items fetched successfully', $cartItems);
        } catch (\Throwable $th) {
            Log::error('Error fetching cart items', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    /**
     * Ambil cart item berdasarkan ID
     */
    public function show($id)
    {
        try {
            $cartItem = Cart::find($id);
            if (!$cartItem) return ResponseHelper::errorResponse('Cart item not found', 404);

            return ResponseHelper::successResponse('Cart item fetched successfully', $cartItem);
        } catch (\Throwable $th) {
            Log::error('Error fetching cart item', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    /**
     * Tambah item ke cart
     */
    public function store(Request $request)
    {
        $validate = $this->validate($request, [
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1'
        ]);

        try {
            // Ambil detail produk dari product-service
            $product = $this->getProduct($validate['product_id']);
            if (!$product) return ResponseHelper::errorResponse('Product not found', 404);

            // Simpan cart item
            $cartItem = Cart::create([
                'product_id' => $validate['product_id'],
                'name'       => $product['name'],
                'quantity'   => $validate['quantity'],
                'price'      => $product['price'] * $validate['quantity']
            ]);

            return ResponseHelper::successResponse('Cart item created successfully', $cartItem);
        } catch (\Throwable $th) {
            Log::error('Error creating cart item', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    /**
     * Update quantity cart item
     */
    public function update(Request $request, $id)
    {
        $validate = $this->validate($request, [
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            $cartItem = Cart::find($id);
            if (!$cartItem) return ResponseHelper::errorResponse('Cart item not found', 404);

            // Ambil data produk terbaru
            $product = $this->getProduct($cartItem->product_id);
            if (!$product || !isset($product['price'])) {
                return ResponseHelper::errorResponse('Product not found or price unavailable', 404);
            }

            $cartItem->quantity = $validate['quantity'];
            $cartItem->price = $product['price'] * $validate['quantity'];
            $cartItem->save();

            return ResponseHelper::successResponse('Cart item updated successfully', $cartItem);
        } catch (\Throwable $th) {
            Log::error('Error updating cart item', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return ResponseHelper::errorResponse($th->getMessage());
        }
    }

    /**
     * Hapus cart item
     */
    public function destroy($id)
    {
        try {
            $cartItem = Cart::find($id);
            if (!$cartItem) return ResponseHelper::errorResponse('Cart item not found', 404);

            $cartItem->delete();
            return ResponseHelper::successResponse('Cart item deleted successfully');
        } catch (\Throwable $th) {
            Log::error('Error deleting cart item', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return ResponseHelper::errorResponse($th->getMessage());
        }
    }
}
