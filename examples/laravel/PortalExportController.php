<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SumsubSdk\Sumsub\Services\PortalDataService;
use SumsubSdk\Sumsub\Exceptions\ApiException;

/**
 * Controller for exporting Sumsub data in Portal format
 */
class PortalExportController extends Controller
{
    protected PortalDataService $portalService;

    public function __construct(PortalDataService $portalService)
    {
        $this->portalService = $portalService;
    }

    /**
     * Get user data in portal format by referral code
     *
     * Route: GET /api/portal/export/{referralCode}
     * Query params: ?include_images=true
     *
     * @param string $referralCode
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(string $referralCode, Request $request)
    {
        $includeImages = $request->query('include_images', 'false') === 'true';

        try {
            $data = $this->portalService->getUserData(
                externalUserId: $referralCode,
                includeImages: $includeImages
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $e->getCorrelationId()
            ], $e->getStatusCode());
        }
    }

    /**
     * Get multiple users data in portal format
     *
     * Route: POST /api/portal/export/bulk
     * Body: { "referral_codes": ["USER123", "USER456"] }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'referral_codes' => 'required|array|min:1|max:100',
            'referral_codes.*' => 'string',
            'include_images' => 'boolean',
        ]);

        $referralCodes = $request->input('referral_codes');
        $includeImages = $request->input('include_images', false);

        try {
            $data = $this->portalService->getBulkUserData(
                externalUserIds: $referralCodes,
                includeImages: $includeImages
            );

            return response()->json([
                'success' => true,
                'total' => count($data),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download user data as JSON file
     *
     * Route: GET /api/portal/download/{referralCode}
     *
     * @param string $referralCode
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function download(string $referralCode, Request $request)
    {
        $includeImages = $request->query('include_images', 'false') === 'true';

        try {
            $json = $this->portalService->getUserDataJson(
                externalUserId: $referralCode,
                includeImages: $includeImages,
                prettyPrint: true
            );

            return response($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="user_' . $referralCode . '.json"');

        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        }
    }
}

