<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessedSubscriptionOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $productName;
    private float $price;

    public function __construct(string $productName, float $price)
    {
        $this->productName = $productName;
        $this->price = $price;

        Log::info("Job created for subscription: {$productName}");
    }

    public function handle(): void
    {
        $payload = [
            'ProductName' => $this->productName,
            'Price' => $this->price,
            'Timestamp' => now()->toISOString()
        ];

        try {
            Log::info('Sending payload to third-party API', ['payload' => $payload]);

            $response = retry(3, function () use ($payload) {
                return Http::post('https://very-slow-api.com/orders', $payload);
            },100);
            // Adding a delay of 100 miliseconds for preventing the unnecessary calls to Third Party App

            if ($response->successful()) {
                Log::info('Successfully sent subscription to third-party API', ['response' => $response->json()]);
            } else {
                Log::error('Failed to send subscription to third-party API', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                //Optionally, retry the job or handle failure
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending subscription to third-party API', ['exception' => $e->getMessage()]);
            // Handle the exception, e.g., retry or notify
        }
    }
}