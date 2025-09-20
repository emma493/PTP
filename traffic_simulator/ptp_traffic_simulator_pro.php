#!/usr/bin/env php
<?php
/**
 * KsTU Final Year Project - Advanced PTP Traffic Simulator
 * Simulates realistic ad traffic for PTP network tracking endpoints.
 * Target: https://adslinks.ru/ptpv.php?v=7534
 */

// ==================== CONFIGURATION ====================
$TARGET_URL = 'https://adslinks.ru/ptpv.php?v=7534';
$NUMBER_OF_REQUESTS = 10; // Number of total views to simulate
$DELAY_BETWEEN_REQUESTS = [3, 8]; // Min/Max delay between requests in seconds

// ==================== ADVANCED USER AGENTS ====================
$USER_AGENTS = [
    // Desktop Browsers
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    
    // Mobile Devices
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36'
];

// ==================== REFERRER SOURCES ====================
$REFERRERS = [
    'https://www.google.com/search?q=online+shopping+2024',
    'https://www.facebook.com/',
    'https://twitter.com/home',
    'https://www.instagram.com/',
    'https://www.youtube.com/',
    'https://www.pinterest.com/',
    'https://web.whatsapp.com/',
    'https://www.tiktok.com/',
    'https://www.linkedin.com/feed/',
    'https://www.reddit.com/',
    'https://www.amazon.com/',
    'https://www.ebay.com/',
    '' // Direct traffic (no referrer)
];

// ==================== PLATFORM DETECTION FUNCTION ====================
function getPlatformFromUserAgent($userAgent) {
    if (strpos($userAgent, 'Windows') !== false) return 'Windows';
    if (strpos($userAgent, 'Mac') !== false) return 'macOS';
    if (strpos($userAgent, 'Linux') !== false) return 'Linux';
    if (strpos($userAgent, 'iPhone') !== false) return 'iOS';
    if (strpos($userAgent, 'Android') !== false) return 'Android';
    return 'Unknown';
}

// ==================== SIMULATE REAL BROWSER HEADERS ====================
function generateHeaders($userAgent, $referrer = '') {
    $acceptLanguages = [
        'en-US,en;q=0.9',
        'fr-FR,fr;q=0.8,en;q=0.7',
        'de-DE,de;q=0.8,en;q=0.7',
        'es-ES,es;q=0.8,en;q=0.7',
        'pt-BR,pt;q=0.8,en;q=0.7'
    ];
    
    $acceptEncodings = [
        'gzip, deflate, br',
        'gzip, deflate',
        'br, gzip, deflate'
    ];
    
    return [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: ' . $acceptLanguages[array_rand($acceptLanguages)],
        'Accept-Encoding: ' . $acceptEncodings[array_rand($acceptEncodings)],
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0',
        'sec-ch-ua: "Chromium";v="121", "Not A(Brand";v="99"',
        'sec-ch-ua-mobile: ' . (strpos($userAgent, 'Mobile') !== false ? '?1' : '?0'),
        'sec-ch-ua-platform: "' . getPlatformFromUserAgent($userAgent) . '"',
        'User-Agent: ' . $userAgent
    ];
}

// ==================== ADVANCED REQUEST FUNCTION ====================
function makeAdvancedRequest($url, $userAgent, $referrer = '') {
    $ch = curl_init();
    
    $headers = generateHeaders($userAgent, $referrer);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_REFERER => $referrer,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => true, // Capture headers to check for redirects
        CURLOPT_ENCODING => '', // Handle compression automatically
        CURLOPT_COOKIE => generateCookies(),
        CURLOPT_SSL_VERIFYPEER => false, // For testing only
        CURLOPT_SSL_VERIFYHOST => false  // For testing only
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'redirect_url' => $redirectUrl,
        'effective_url' => $effectiveUrl,
        'response' => $response
    ];
}

// ==================== GENERATE REALISTIC COOKIES ====================
function generateCookies() {
    $cookies = [
        'session_id=' . bin2hex(random_bytes(8)),
        'user_id=' . rand(100000, 999999),
        'last_visit=' . time(),
        'tracking_consent=true',
        'preferences=language:en|theme:light'
    ];
    
    return implode('; ', $cookies);
}

// ==================== ANALYZE RESPONSE ====================
function analyzeResponse($result, $requestId) {
    echo "  Request #$requestId:\n";
    echo "    HTTP Code: " . $result['http_code'] . "\n";
    echo "    Effective URL: " . $result['effective_url'] . "\n";
    
    if ($result['redirect_url']) {
        echo "    Redirected to: " . $result['redirect_url'] . "\n";
    }
    
    // Check for common tracking parameters
    if (strpos($result['effective_url'], 'google') !== false) {
        echo "    [INFO] Detected Google Analytics tracking\n";
    }
    
    if (strpos($result['effective_url'], 'facebook') !== false) {
        echo "    [INFO] Detected Facebook Pixel tracking\n";
    }
    
    // Basic content analysis
    $contentLength = strlen($result['response']);
    echo "    Response Size: " . $contentLength . " bytes\n";
    
    if ($contentLength < 500) {
        echo "    [INFO] Likely a tracking pixel or redirect\n";
    }
    
    return $result['http_code'] == 200;
}

// ==================== MAIN EXECUTION ====================
echo "==================================================\n";
echo "    ADVANCED PTP TRAFFIC SIMULATOR\n";
echo "    KsTU Computer Science - Final Year Project\n";
echo "==================================================\n";
echo "Target URL: " . $TARGET_URL . "\n";
echo "Total Requests: " . $NUMBER_OF_REQUESTS . "\n";
echo "Delay Range: " . $DELAY_BETWEEN_REQUESTS[0] . "-" . $DELAY_BETWEEN_REQUESTS[1] . " seconds\n";
echo "==================================================\n\n";

$successfulRequests = 0;
$failedRequests = 0;
$requestDetails = [];

for ($i = 1; $i <= $NUMBER_OF_REQUESTS; $i++) {
    echo "Sending request $i of $NUMBER_OF_REQUESTS...\n";
    
    $userAgent = $USER_AGENTS[array_rand($USER_AGENTS)];
    $referrer = $REFERRERS[array_rand($REFERRERS)];
    
    $result = makeAdvancedRequest($TARGET_URL, $userAgent, $referrer);
    
    if (analyzeResponse($result, $i)) {
        $successfulRequests++;
        echo "    Status: ✓ SUCCESS\n";
    } else {
        $failedRequests++;
        echo "    Status: ✗ FAILED\n";
    }
    
    $requestDetails[] = [
        'request_id' => $i,
        'user_agent' => $userAgent,
        'referrer' => $referrer,
        'result' => $result
    ];
    
    // Random delay between requests
    if ($i < $NUMBER_OF_REQUESTS) {
        $delay = rand($DELAY_BETWEEN_REQUESTS[0], $DELAY_BETWEEN_REQUESTS[1]);
        echo "    Waiting $delay seconds...\n\n";
        sleep($delay);
    }
}

// ==================== GENERATE DETAILED REPORT ====================
echo "==================================================\n";
echo "                SIMULATION REPORT\n";
echo "==================================================\n";
echo "Total Requests: " . $NUMBER_OF_REQUESTS . "\n";
echo "Successful: " . $successfulRequests . " (" . round(($successfulRequests/$NUMBER_OF_REQUESTS)*100, 1) . "%)\n";
echo "Failed: " . $failedRequests . " (" . round(($failedRequests/$NUMBER_OF_REQUESTS)*100, 1) . "%)\n\n";

echo "User Agent Distribution:\n";
$uaCount = [];
foreach ($requestDetails as $request) {
    $shortUA = substr($request['user_agent'], 0, 50) . "...";
    if (!isset($uaCount[$shortUA])) $uaCount[$shortUA] = 0;
    $uaCount[$shortUA]++;
}

foreach ($uaCount as $ua => $count) {
    echo "  - " . $ua . " (" . $count . "x)\n";
}

echo "\nReferrer Distribution:\n";
$refCount = [];
foreach ($requestDetails as $request) {
    $ref = $request['referrer'] ?: 'Direct';
    if (!isset($refCount[$ref])) $refCount[$ref] = 0;
    $refCount[$ref]++;
}

foreach ($refCount as $ref => $count) {
    echo "  - " . $ref . " (" . $count . "x)\n";
}

echo "\n==================================================\n";
echo "NOTE: This simulator is for academic research only.\n";
echo "It demonstrates PTP ad tracking mechanisms for educational purposes.\n";
echo "==================================================\n";

// Save detailed log for analysis
file_put_contents('ptp_simulation_log.txt', json_encode($requestDetails, JSON_PRETTY_PRINT));
echo "\nDetailed log saved to: ptp_simulation_log.txt\n";
?>
