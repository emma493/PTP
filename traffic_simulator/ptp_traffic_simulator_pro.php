#!/usr/bin/env php
<?php
/**
 * KsTU Final Year Project - PTP View Generator
 * Interactive bot for generating PTP ad views
 */

// ==================== INTERACTIVE PROMPTS ====================
function ask($question, $default = '')
{
    echo "🤖 " . $question;
    if ($default !== '') {
        echo " [Default: $default]";
    }
    echo ": ";

    $handle = fopen("php://stdin", "r");
    $answer = trim(fgets($handle));
    fclose($handle);

    return ($answer === '' && $default !== '') ? $default : $answer;
}

function confirm($question)
{
    echo "❓ " . $question . " (y/N): ";
    $handle = fopen("php://stdin", "r");
    $answer = strtolower(trim(fgets($handle)));
    fclose($handle);

    return $answer === 'y' || $answer === 'yes';
}

// ==================== BANNER ====================
function showBanner()
{
    echo "=================================================\n";
    echo "           PTP VIEW GENERATOR BOT\n";
    echo "     KsTU Computer Science - Final Year Project\n";
    echo "=================================================\n";
    echo "🤖  This bot simulates views for PTP ad networks\n";
    echo "📊  Designed for academic research purposes\n";
    echo "⚠️   Use only on servers you have permission to test\n";
    echo "=================================================\n\n";
}

// ==================== CONFIGURATION ====================
function getConfiguration()
{
    $config = [];

    // Get target URL
    $config['target_url'] = ask("Enter the PTP tracking URL", "https://adslinks.ru/ptpv.php?v=7534");

    // Get number of views
    $views = ask("How many views do you want to generate?", "10");
    $config['num_views'] = intval($views);

    // Get delay between requests
    $delay = ask("Delay between views (seconds)", "3");
    $config['delay'] = intval($delay);

    // Ask for advanced options
    if (confirm("Show advanced options?")) {
        $config['use_proxy'] = confirm("Use proxy rotation?");
        $config['random_delay'] = confirm("Use random delay between views?");

        if ($config['random_delay']) {
            $min = ask("Minimum delay (seconds)", "2");
            $max = ask("Maximum delay (seconds)", "6");
            $config['delay_range'] = [intval($min), intval($max)];
        }
    } else {
        $config['use_proxy'] = false;
        $config['random_delay'] = false;
        $config['delay_range'] = [2, 6];
    }

    return $config;
}

// ==================== USER AGENTS ====================
$USER_AGENTS = [
    // Desktop
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',

    // Mobile
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Android 14; Mobile; rv:109.0) Gecko/111.0 Firefox/111.0'
];

$REFERRERS = [
    'https://www.google.com/',
    'https://www.facebook.com/',
    'https://www.youtube.com/',
    'https://www.twitter.com/',
    'https://www.instagram.com/',
    'https://www.reddit.com/',
    'https://www.amazon.com/',
    'https://www.tiktok.com/',
    ''
];

// ==================== TRACKING FUNCTIONS ====================
function generateRandomIP()
{
    return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
}

function generateViewParameters()
{
    global $USER_AGENTS, $REFERRERS;

    $userAgent = $USER_AGENTS[array_rand($USER_AGENTS)];
    $referrer = $REFERRERS[array_rand($REFERRERS)];

    return [
        'ua' => $userAgent,
        'ref' => $referrer,
        'ip' => generateRandomIP(),
        'ts' => time(),
        'r' => rand(100000, 999999)
    ];
}

function sendViewRequest($url, $params)
{
    $ch = curl_init();

    // Build headers
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'User-Agent: ' . $params['ua'],
        'Referer: ' . $params['ref'],
        'X-Forwarded-For: ' . $params['ip'],
        'X-Real-IP: ' . $params['ip']
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIE => 'view_id=' . uniqid() . '_' . rand(1000, 9999),
        CURLOPT_NOBODY => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    curl_close($ch);

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'effective_url' => $effectiveUrl,
        'user_agent' => $params['ua'],
        'ip_address' => $params['ip']
    ];
}

// ==================== MAIN EXECUTION ====================
function main()
{
    showBanner();

    // Get configuration from user
    $config = getConfiguration();

    echo "\n";
    echo "🚀 Starting view generation...\n";
    echo "📋 Configuration:\n";
    echo "   Target URL: " . $config['target_url'] . "\n";
    echo "   Views to generate: " . $config['num_views'] . "\n";
    echo "   Delay: " . $config['delay'] . "s between views\n";
    echo "=================================================\n\n";

    $successfulViews = 0;
    $failedViews = 0;
    $viewLog = [];

    // Generate views
    for ($i = 1; $i <= $config['num_views']; $i++) {
        echo "👁️  Generating view $i of " . $config['num_views'] . "...\n";

        $params = generateViewParameters();
        $result = sendViewRequest($config['target_url'], $params);

        if ($result['success']) {
            echo "   ✅ SUCCESS - HTTP " . $result['http_code'] . "\n";
            $successfulViews++;
        } else {
            echo "   ❌ FAILED - HTTP " . $result['http_code'] . "\n";
            $failedViews++;
        }

        echo "   📱 Device: " . (strpos($result['user_agent'], 'Mobile') !== false ? 'Mobile' : 'Desktop') . "\n";
        echo "   🌐 IP: " . $result['ip_address'] . "\n";

        $viewLog[] = [
            'view_number' => $i,
            'timestamp' => date('Y-m-d H:i:s'),
            'result' => $result
        ];

        // Delay between views
        if ($i < $config['num_views']) {
            $delay = $config['random_delay'] ?
                rand($config['delay_range'][0], $config['delay_range'][1]) :
                $config['delay'];

            echo "   ⏳ Waiting $delay seconds...\n\n";
            sleep($delay);
        }
    }

    // Generate report
    echo "\n=================================================\n";
    echo "                 GENERATION REPORT\n";
    echo "=================================================\n";
    echo "📊 Total views attempted: " . $config['num_views'] . "\n";
    echo "✅ Successful views: " . $successfulViews . "\n";
    echo "❌ Failed views: " . $failedViews . "\n";
    echo "📈 Success rate: " . round(($successfulViews / $config['num_views']) * 100, 1) . "%\n";

    if ($successfulViews > 0) {
        echo "🎉 Views generated successfully!\n";
    } else {
        echo "⚠️  No views were registered. Possible reasons:\n";
        echo "   - Invalid or expired tracking URL\n";
        echo "   - URL requires specific parameters\n";
        echo "   - Server-side validation failed\n";
        echo "   - IP or user agent filtering\n";
    }

    echo "=================================================\n";

    // Save log file
    $logFilename = 'view_generation_log_' . date('Y-m-d_His') . '.json';
    file_put_contents($logFilename, json_encode($viewLog, JSON_PRETTY_PRINT));
    echo "📁 Log saved to: $logFilename\n";

    echo "=================================================\n";
    echo "Note: For academic research purposes only\n";
    echo "=================================================\n";
}

// Start the bot
main();
?>
