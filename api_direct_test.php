<!DOCTYPE html>
<html>
<head>
    <title>API Direct Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
        pre { background: #f8f9fa; padding: 10px; overflow: auto; max-height: 200px; }
    </style>
</head>
<body>
    <h1>API Direct Test</h1>
    
    <div class="test-section">
        <h2>Test 1: Hashtags API</h2>
        <button onclick="testAPI('hashtags', 'action=all&limit=5')">Test Hashtags</button>
        <div id="hashtags-result"></div>
    </div>

    <div class="test-section">
        <h2>Test 2: Users API</h2>
        <button onclick="testAPI('users', 'action=search&q=')">Test Users</button>
        <div id="users-result"></div>
    </div>

    <div class="test-section">
        <h2>Test 3: Posts API</h2>
        <button onclick="testAPI('posts', 'action=all&limit=5')">Test Posts</button>
        <div id="posts-result"></div>
    </div>

    <script>
    async function testAPI(endpoint, params) {
        const resultDiv = document.getElementById(endpoint + '-result');
        const url = `api/${endpoint}.php?${params}`;
        
        resultDiv.innerHTML = '<p>Testing...</p>';
        
        try {
            const response = await fetch(url);
            const text = await response.text();
            
            resultDiv.innerHTML = `
                <p><strong>Status:</strong> ${response.status} ${response.statusText}</p>
                <p><strong>Response:</strong></p>
                <pre>${text}</pre>
            `;
            
            // Try to parse as JSON
            try {
                const json = JSON.parse(text);
                resultDiv.className = 'success';
                resultDiv.innerHTML += '<p><strong>✅ Valid JSON Response</strong></p>';
            } catch (e) {
                resultDiv.className = 'error';
                resultDiv.innerHTML += '<p><strong>❌ Invalid JSON Response</strong></p>';
            }
            
        } catch (error) {
            resultDiv.className = 'error';
            resultDiv.innerHTML = `<p><strong>❌ Network Error:</strong> ${error.message}</p>`;
        }
    }
    </script>
</body>
</html>