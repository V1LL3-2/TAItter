<?php
// Quick diagnostic to see what's actually being returned by the API
echo "<h1>API Response Test</h1>";

// Test the exact API calls that are failing
$api_calls = [
    'api/hashtags.php?action=all&limit=5',
    'api/users.php?action=search&q=',
    'api/posts.php?action=timeline&limit=5'
];

foreach ($api_calls as $url) {
    echo "<h2>Testing: $url</h2>";
    
    // Start output buffering to capture any output
    ob_start();
    
    // Set up the environment
    $_GET = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $_GET);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    try {
        // Include the API file and capture its output
        include $url;
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage();
    }
    
    // Get the captured output
    $output = ob_get_clean();
    
    echo "<h3>Raw Output:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow: auto;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    echo "<h3>Output Analysis:</h3>";
    if (empty($output)) {
        echo "❌ No output<br>";
    } elseif (strpos($output, '<br') !== false) {
        echo "❌ Contains HTML/PHP errors<br>";
    } elseif (json_decode($output) === null) {
        echo "❌ Not valid JSON<br>";
    } else {
        echo "✅ Valid JSON<br>";
    }
    
    echo "<hr>";
}

// Also test individual API endpoints via curl/file_get_contents
echo "<h1>Direct HTTP Test</h1>";
foreach ($api_calls as $url) {
    $full_url = "http://localhost" . $_SERVER['REQUEST_URI'] . $url;
    $full_url = str_replace('quick_fix_test.php', '', $full_url) . $url;
    
    echo "<h3>$url</h3>";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);
    
    $response = @file_get_contents($full_url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to fetch<br>";
    } else {
        echo "Response length: " . strlen($response) . " characters<br>";
        echo "First 200 chars: <pre>" . htmlspecialchars(substr($response, 0, 200)) . "</pre>";
        
        if (json_decode($response) !== null) {
            echo "✅ Valid JSON response<br>";
        } else {
            echo "❌ Invalid JSON response<br>";
        }
    }
    echo "<hr>";
}
?>