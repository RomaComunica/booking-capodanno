<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reindirizzamento a Nexi...</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg-primary: #121212;
            --bg-card: rgba(255, 255, 255, 0.05);
            --text-primary: #FFFFFF;
            --text-secondary: #A0A0A0;
            --accent-gold: #FFD700;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        .container {
            text-align: center;
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            max-width: 400px;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid var(--accent-gold);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h3 {
            color: var(--text-primary);
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h3>Reindirizzamento a Nexi...</h3>
        <p>Attendere prego, verrai reindirizzato al gateway di pagamento sicuro.</p>
    </div>
    
    <form id="nexiForm" method="POST" action="{{ $gatewayUrl }}">
        <input type="hidden" name="alias" value="{{ $alias }}">
        <input type="hidden" name="importo" value="{{ $importo }}">
        <input type="hidden" name="divisa" value="{{ $divisa }}">
        <input type="hidden" name="codTrans" value="{{ $codTrans }}">
        <input type="hidden" name="url" value="{{ $returnUrl }}">
        <input type="hidden" name="url_back" value="{{ $cancelUrl }}">
        <input type="hidden" name="mac" value="{{ $mac }}">
    </form>
    
    <script>
        // Auto-submit form after 1.5 second
        setTimeout(function() {
            document.getElementById('nexiForm').submit();
        }, 1500);
    </script>
</body>
</html>
