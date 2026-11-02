<?php
/**
 * Sync Test Page — posts sample data to test_sync/test_endpoint and shows the response.
 * (Developer utility; no auth required by the controller.)
 */
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sync Endpoint Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 14px; color: #1f2937; background: #f3f4f6; padding: 30px 16px; }
        .card { max-width: 620px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 10px 28px rgba(0,0,0,.1); overflow: hidden; }
        .head { background: #111827; color: #fff; padding: 18px 24px; }
        .head h1 { font-size: 17px; }
        .head p { font-size: 12px; opacity: .8; margin-top: 3px; }
        .body { padding: 24px; }
        label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; margin: 14px 0 6px; }
        input { width: 100%; padding: 10px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: #f9fafb; }
        input:focus { outline: none; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        button { margin-top: 20px; width: 100%; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 700; cursor: pointer; }
        button:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(102,126,234,.4); }
        pre { margin-top: 20px; background: #111827; color: #a7f3d0; border-radius: 10px; padding: 16px; font-size: 12.5px; overflow-x: auto; min-height: 60px; white-space: pre-wrap; }
        .hint { font-size: 11.5px; color: #9ca3af; margin-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1>Sync Endpoint Test</h1>
            <p>POSTs sample data to test_sync/test_endpoint and displays the JSON response</p>
        </div>
        <div class="body">
            <label for="name">Name</label>
            <input type="text" id="name" value="Test Student">

            <label for="email">Email</label>
            <input type="email" id="email" value="test@example.com">

            <label for="amount">Amount</label>
            <input type="number" id="amount" step="0.01" value="50.00">

            <button onclick="runTest()">Send Test Payload</button>
            <div class="hint">Endpoint: <?php echo site_url('test_sync/test_endpoint'); ?></div>

            <pre id="output">Response will appear here…</pre>
        </div>
    </div>

    <script>
        function runTest() {
            var out = document.getElementById('output');
            out.textContent = 'Sending…';
            var body = new URLSearchParams();
            body.append('name', document.getElementById('name').value);
            body.append('email', document.getElementById('email').value);
            body.append('amount', document.getElementById('amount').value);
            fetch('<?php echo site_url('test_sync/test_endpoint'); ?>', {
                method: 'POST',
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(j) { out.textContent = JSON.stringify(j, null, 2); })
            .catch(function(e) { out.textContent = 'Request failed: ' + e; });
        }
    </script>
</body>
</html>
