<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SumsubSdk\Sumsub\Client\SumsubClient;
use SumsubSdk\Sumsub\Exceptions\ApiException;

/**
 * Example Laravel Controller for Sumsub Integration
 * Works with referral_code only, no User model required
 *
 * Required environment variables:
 * - SUMSUB_APP_TOKEN - Your Sumsub application token
 * - SUMSUB_APP_SECRET - Your Sumsub secret key
 * - SUMSUB_LEVEL_NAME - Verification level (default: basic-kyc-level)
 */
class SumsubController extends Controller
{
    protected SumsubClient $client;
    protected string $levelName;

    public function __construct(SumsubClient $client)
    {
        $this->client = $client;
        $this->levelName = config('sumsub.level_name', 'basic-kyc-level');
    }

    /**
     * Show Sumsub verification widget for specific referral code
     *
     * Route: GET /sumsub/verify/{referralCode}
     *
     * @param string $referralCode User's referral code (external user ID)
     * @param Request $request Optional email and phone from query params
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(string $referralCode, Request $request)
    {
        try {
            // Validate referral code format if needed
            if (empty($referralCode) || strlen($referralCode) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid referral code'
                ], 400);
            }

            // Generate access token for WebSDK
            $tokenData = $this->client->generateAccessToken(
                externalUserId: $referralCode,
                levelName: $this->levelName
            );

            return view('sumsub.widget', [
                'token' => $tokenData['token'],
                'referral_code' => $referralCode,
                'email' => $request->query('email', ''),
                'phone' => $request->query('phone', ''),
            ]);

        } catch (ApiException $e) {
            Log::error('Sumsub token generation error', [
                'referral_code' => $referralCode,
                'error' => $e->getMessage(),
                'correlation_id' => $e->getCorrelationId(),
            ]);

            return view('sumsub.widget', [
                'token' => null,
                'referral_code' => $referralCode,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

