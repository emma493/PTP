#!/usr/bin/env php
<?php
/**
 * KsTU Final Year Project - PTP Tracking Pixel Simulator
 * Advanced simulator for ad tracking endpoints
 */

// ==================== CONFIGURATION ====================
$TARGET_URL = 'https://adslinks.ru/ptpv.php?v=7534';
$NUMBER_OF_REQUESTS = 5;
$DELAY_BETWEEN_REQUESTS = [2, 5];

// ==================== USER AGENTS ====================
$USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36'
];

// ==================== TRACKING PARAMETERS ====================
function generateTrackingParams() {
    return [
        'v' => '7534',
        'r' => time(),
        'cb' => rand(100000, 999999),
        'ua' => urlencode($USER_AGENTS[array_rand($USER_AGENTS)]),
        'ip' => generateRandomIP(),
        'ref' => getRandomReferrer(),
        'ts' => time()
    ];
}

function generateRandomIP() {
    return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
}

function getRandomReferrer() {
    $refs = [
        'https://www.google.com/',
        'https://www.facebook.com/',
        'https://www.youtube.com/',
        'https://www.amazon.com/',
        ''
    ];
    return urlencode($refs[array_rand($refs)]);
}

// ==================== ADVANCED REQUEST ====================
function makeTrackingRequest($url, $params) {
    $queryString = http_build_query($params);
    $fullUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . $queryString;
    
    $ch = curl_init();
    
    $headers = [
        'Accept: image/webp,image/*,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'User-Agent: ' . $params['ua'],
        'Referer: ' . urldecode($params['ref']),
        'X-Forwarded-For: ' . $params['ip'],
        'X-Real-IP: ' . $params['ip'],
        'CF-Connecting-IP: ' . $params['ip']
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIE => 'tracking_id=' . bin2hex(random_bytes(8)) . '; session=' . time()
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'content_type' => $contentType,
        'size' => $size,
        'url' => $fullUrl
    ];
}

// ==================== MAIN EXECUTION ====================
echo "========================================\n";
echo "   PTP TRACKING PIXEL SIMULATOR\n";
echo "   KsTU Computer Science Project\n";
echo "========================================\n";
echo "Target: " . $TARGET_URL . "\n";
echo "Requests: " . $NUMBER_OF_REQUESTS . "\n";
echo "========================================\n\n";

$results = [];

for ($i = 1; $i <= $NUMBER_OF_REQUESTS; $i++) {
    echo "🔄 Sending request $i/$NUMBER_OF_REQUESTS...\n";
    
    $params = generateTrackingParams();
    $result = makeTrackingRequest($TARGET_URL, $params);
    
    echo "   Status: HTTP " . $result['http_code'] . "\n";
    echo "   Type: " . ($result['content_type'] ?: 'Unknown') . "\n";
    echo "   Size: " . $result['size'] . " bytes\n";
    echo "   IP: " . $params['ip'] . "\n";
    
    if ($result['http_code'] == 200) {
        echo "   ✅ View counted successfully!\n";
    } else {
        echo "   ❌ Possible issue (Code: " . $result['http_code'] . ")\n";
    }
    
    $results[] = $result;
    
    if ($i < $NUMBER_OF_REQUESTS) {
        $delay = rand($DELAY_BETWEEN_REQUESTS[0], $DELAY_BETWEEN_REQUESTS[1]);
        echo "   ⏳ Waiting $delay seconds...\n\n";
        sleep($delay);
    }
}

// ==================== ANALYSIS ====================
echo "\n========================================\n";
echo "           RESULTS ANALYSIS\n";
echo "========================================\n";

$successCount = 0;
foreach ($results as $result) {
    if ($result['http_code'] == 200) {
        $successCount++;
    }
}

echo "Successful views: $successCount/$NUMBER_OF_REQUESTS\n";
echo "Success rate: " . round(($successCount/$NUMBER_OF_REQUESTS) * 100, 1) . "%\n";

if ($successCount > 0) {
    echo "✅ Views are being generated!\n";
} else {
    echo "❌ No views registered. Possible reasons:\n";
    echo "   - URL might be expired/invalid\n";
    echo "   - Server requires specific parameters\n";
    echo "   - IP might be blocked\n";
    echo "   - Needs JavaScript execution\n";
}

echo "========================================\n";
echo "Note: For academic research only\n";
echo "========================================\n";
?>
