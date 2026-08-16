<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class WacApiTest
 *
 * Feature tests verifying RESTful API routes, JWT authentication protection,
 * form request validation rules, and JSON API Resources.
 */
class WacApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'API Test User',
            'email' => 'apitest@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->token = auth('api')->login($this->user);

        $this->product = Product::create([
            'sku' => 'PROD-API-001',
            'name' => 'API Test Product',
            'current_stock_qty' => 0,
            'current_total_value' => '0.0000',
        ]);
    }

    /**
     * Test Case 7: JWT Authentication Flow (Register, Login, Me, Logout)
     */
    public function test_jwt_authentication_flow_register_login_me_logout(): void
    {
        // 1. Register
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);

        // 2. Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);

        $token = $loginResponse->json('access_token');

        // 3. Me Profile
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson(['email' => 'newuser@example.com']);

        // 4. Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
    }

    /**
     * Test Case 8: Store Purchase Transaction via API
     */
    public function test_store_purchase_transaction_via_api(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/purchases', [
                'product_id' => $this->product->id,
                'transaction_date' => '2026-01-01',
                'quantity' => 100,
                'unit_cost' => '10.00',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'purchase')
            ->assertJsonPath('data.quantity', 100)
            ->assertJsonPath('data.running_qty', 100)
            ->assertJsonPath('data.running_total_value', '1000.0000')
            ->assertJsonPath('data.product.sku', 'PROD-API-001');
    }

    /**
     * Test Case 9: Store Sale Transaction Allocates COGS Snapshot via API
     */
    public function test_store_sale_transaction_allocates_cogs_snapshot_via_api(): void
    {
        // Purchase 100 @ 10.00
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/purchases', [
                'product_id' => $this->product->id,
                'transaction_date' => '2026-01-01',
                'quantity' => 100,
                'unit_cost' => '10.00',
            ]);

        // Sale 10 units @ 20.00
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/sales', [
                'product_id' => $this->product->id,
                'transaction_date' => '2026-01-05',
                'quantity' => 10,
                'unit_price' => '20.00',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'sale')
            ->assertJsonPath('data.cogs_unit_cost', '10.0000')
            ->assertJsonPath('data.total_cogs', '100.0000')
            ->assertJsonPath('data.running_qty', 90)
            ->assertJsonPath('data.running_total_value', '900.0000');
    }

    /**
     * Test Case 10: Single Active Daily Transaction Rule Validation
     */
    public function test_single_active_daily_transaction_rule_validation(): void
    {
        // First purchase on 2026-01-01
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/purchases', [
                'product_id' => $this->product->id,
                'transaction_date' => '2026-01-01',
                'quantity' => 50,
                'unit_cost' => '5.00',
            ])->assertStatus(201);

        // Second transaction on same date should fail validation with 422
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/purchases', [
                'product_id' => $this->product->id,
                'transaction_date' => '2026-01-01',
                'quantity' => 20,
                'unit_cost' => '5.00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['transaction_date']);
    }

    /**
     * Test Case 11: Update and Soft Delete Transaction via API (Bonus 2)
     */
    public function test_update_and_soft_delete_transaction_via_api(): void
    {
        // 1. Purchase
        $purchaseResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/purchases', [
                'product_id' => $this->product->id,
                'transaction_date' => '2026-01-01',
                'quantity' => 100,
                'unit_cost' => '10.00',
            ]);

        $txId = $purchaseResponse->json('data.id');

        // 2. Update transaction quantity via PUT
        $updateResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/transactions/{$txId}", [
                'quantity' => 200,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.running_qty', 200)
            ->assertJsonPath('data.running_total_value', '2000.0000');

        // 3. Soft Delete transaction via DELETE
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/transactions/{$txId}");

        $deleteResponse->assertStatus(200)
            ->assertJson(['message' => 'Transaction soft-deleted and timeline recalculated successfully.']);

        $this->assertSoftDeleted('stock_transactions', ['id' => $txId]);
    }

    /**
     * Test Case 12: Unauthenticated Request Rejected with 401
     */
    public function test_unauthenticated_request_rejected_with_401(): void
    {
        auth('api')->logout();

        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }
}
