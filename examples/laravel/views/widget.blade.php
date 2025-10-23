<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Identity Verification - Sumsub</title>

    <!-- Sumsub WebSDK -->
    <script src="https://static.sumsub.com/idensic/static/sns-websdk-builder.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .error {
            background: #fee;
            border: 2px solid #fcc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            color: #c33;
        }

        .error h3 {
            margin-bottom: 10px;
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #sumsub-websdk-container {
            min-height: 600px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-verified {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .info-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-section h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .info-section ul {
            list-style: none;
            padding-left: 0;
        }

        .info-section li {
            padding: 8px 0;
            color: #666;
        }

        .info-section li:before {
            content: "✓ ";
            color: #667eea;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Identity Verification</h1>
            <p>Please complete the verification process to continue</p>
            @if(isset($referral_code))
                <span class="status-badge status-pending">Referral Code: {{ $referral_code }}</span>
            @endif
        </div>

        <div class="content">
            @if(isset($error) && $error)
                <div class="error">
                    <h3>⚠️ Error</h3>
                    <p>{{ $error }}</p>
                    <p style="margin-top: 10px; font-size: 14px;">
                        Please contact support or try again later.
                    </p>
                </div>
            @endif

            @if(!isset($token) || !$token)
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>Unable to load verification widget. Please refresh the page or contact support.</p>
                </div>
            @else
                <div class="info-section">
                    <h3>📋 What you'll need:</h3>
                    <ul>
                        <li>A valid government-issued ID (Passport, ID Card, or Driver's License)</li>
                        <li>A device with a camera for selfie verification</li>
                        <li>Good lighting conditions</li>
                        <li>5-10 minutes to complete the process</li>
                    </ul>
                </div>

                <div id="sumsub-websdk-container"></div>
            @endif
        </div>
    </div>

    @if(isset($token) && $token)

    <script>
    function launchWebSdk(accessToken, applicantEmail, applicantPhone) {
        let snsWebSdkInstance = snsWebSdk
            .init(accessToken, () => this.getNewAccessToken())
            .withConf({
                lang: "en",
                email: applicantEmail,
                phone: applicantPhone,
            })
            .withOptions({ addViewportTag: false, adaptIframeHeight: true })
            .on("idCheck.onStepCompleted", (payload) => {
                console.log("onStepCompleted", payload);
            })
            .on("idCheck.onError", (error) => {
                console.log("onError", error);
            })
            .onMessage((type, payload) => {

                if(type == 'idCheck.onApplicantSubmitted') {
                    window.location.href = '/';
                }
            })
            .build();
        snsWebSdkInstance.launch("#sumsub-websdk-container");
    }

    // Requests a new access token from the backend side.
    function getNewAccessToken() {
        return Promise.resolve('{{ $token }}');
    }

    launchWebSdk('{{ $token }}');
</script>
    @endif
</body>
</html>



